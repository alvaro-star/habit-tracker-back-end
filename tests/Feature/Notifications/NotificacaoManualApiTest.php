<?php

namespace Tests\Feature\Notifications;

use App\Jobs\EnviarNotificacaoManual;
use App\Models\User;
use App\Notifications\NotificacaoManual;
use App\Services\NotificacaoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\ServerUnavailable;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Laravel\Sanctum\Sanctum;
use Mockery;
use NotificationChannels\Fcm\FcmMessage;
use Tests\TestCase;

class NotificacaoManualApiTest extends TestCase
{
    use RefreshDatabase;

    private User $remetente;

    private User $destinatario;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-22 10:15:30');
        config(['notifications.push_ativo' => false]);
        $this->remetente = User::factory()->create();
        $this->destinatario = User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function payload(array $dados = []): array
    {
        return [
            'fk_id_usuario' => $this->destinatario->id,
            'titulo' => 'Hora de estudar',
            'texto' => 'Não esqueça do seu hábito de hoje.',
            'data' => '2026-11-23',
            'horario' => '20:00',
            ...$dados,
        ];
    }

    private function enviar(): bool
    {
        return app(NotificacaoService::class)->enviarNotificacaoManual(
            $this->destinatario->id, $this->remetente->id, 'Hora de estudar', 'Não esqueça do seu hábito de hoje.',
        );
    }

    public function test_todas_as_rotas_de_notificacoes_exigem_autenticacao(): void
    {
        $rotas = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($rota) => str_starts_with($rota->uri(), 'api/notifications'));

        $this->assertCount(3, $rotas);

        foreach ($rotas as $rota) {
            $metodo = collect($rota->methods())->reject(fn ($m) => $m === 'HEAD')->first();
            $uri = '/'.str_replace('{id}', '1', $rota->uri());

            $this->json($metodo, $uri, $this->payload())->assertUnauthorized();
        }
    }

    public function test_post_agenda_o_job_no_dia_e_horario_escolhidos(): void
    {
        Queue::fake();
        Sanctum::actingAs($this->remetente);

        $this->postJson('/api/notifications', $this->payload())
            ->assertAccepted()
            ->assertJsonPath('data.fk_id_usuario', $this->destinatario->id)
            ->assertJsonPath('data.titulo', 'Hora de estudar')
            ->assertJsonPath('data.enviar_em', '2026-11-23T20:00:00-04:00');

        Queue::assertPushed(EnviarNotificacaoManual::class, 1);
        Queue::assertPushed(EnviarNotificacaoManual::class, fn (EnviarNotificacaoManual $job) => $job->destinatarioId === $this->destinatario->id
            && $job->remetenteId === $this->remetente->id
            && $job->delay->format('Y-m-d H:i:s') === '2026-11-23 20:00:00');
        $this->assertSame(0, $this->destinatario->notifications()->count());
    }

    public function test_usuario_nulo_agenda_um_job_por_usuario(): void
    {
        Queue::fake();
        $terceiro = User::factory()->create();
        Sanctum::actingAs($this->remetente);

        $this->postJson('/api/notifications', $this->payload(['fk_id_usuario' => null]))
            ->assertAccepted()
            ->assertJsonPath('data.fk_id_usuario', null)
            ->assertJsonPath('data.destinatarios', 3);

        Queue::assertPushed(EnviarNotificacaoManual::class, 3);

        foreach ([$this->remetente, $this->destinatario, $terceiro] as $usuario) {
            Queue::assertPushed(EnviarNotificacaoManual::class, fn (EnviarNotificacaoManual $job) => $job->destinatarioId === $usuario->id
                && $job->remetenteId === $this->remetente->id
                && $job->delay->format('Y-m-d H:i:s') === '2026-11-23 20:00:00');
        }
    }

    public function test_usuario_informado_agenda_um_unico_job(): void
    {
        Queue::fake();
        User::factory()->create();
        Sanctum::actingAs($this->remetente);

        $this->postJson('/api/notifications', $this->payload())
            ->assertAccepted()
            ->assertJsonPath('data.destinatarios', 1);

        Queue::assertPushed(EnviarNotificacaoManual::class, 1);
    }

    public function test_campo_ausente_nao_dispara_envio_geral(): void
    {
        Queue::fake();
        Sanctum::actingAs($this->remetente);
        $corpo = $this->payload();
        unset($corpo['fk_id_usuario']);

        $this->postJson('/api/notifications', $corpo)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fk_id_usuario']);

        Queue::assertNothingPushed();
    }

    public function test_horario_passado_nao_agenda_para_ninguem(): void
    {
        Queue::fake();
        Sanctum::actingAs($this->remetente);

        $this->postJson('/api/notifications', $this->payload(['fk_id_usuario' => null, 'data' => '2026-11-21']))
            ->assertUnprocessable();

        Queue::assertNothingPushed();
    }

    public function test_aceita_o_minuto_atual_e_recusa_horario_passado(): void
    {
        Queue::fake();
        Sanctum::actingAs($this->remetente);

        $this->postJson('/api/notifications', $this->payload(['data' => '2026-11-22', 'horario' => '10:15']))->assertAccepted();
        $this->postJson('/api/notifications', $this->payload(['data' => '2026-11-22', 'horario' => '10:14']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['horario']);
        $this->postJson('/api/notifications', $this->payload(['data' => '2026-11-21', 'horario' => '23:00']))
            ->assertUnprocessable();

        Queue::assertPushed(EnviarNotificacaoManual::class, 1);
    }

    public function test_valida_os_campos(): void
    {
        Queue::fake();
        Sanctum::actingAs($this->remetente);

        foreach ([
            'fk_id_usuario' => $this->destinatario->id + 100,
            'titulo' => str_repeat('a', 101),
            'texto' => str_repeat('a', 256),
            'data' => '2026-04-31',
            'horario' => '7h30',
        ] as $campo => $valor) {
            $this->postJson('/api/notifications', $this->payload([$campo => $valor]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors([$campo]);
        }

        Queue::assertNothingPushed();
    }

    public function test_job_grava_no_historico_de_quem_recebe(): void
    {
        $this->assertTrue($this->enviar());

        $notificacao = $this->destinatario->notifications()->sole();
        $this->assertSame(NotificacaoManual::TIPO, $notificacao->type);
        $this->assertSame($this->remetente->id, $notificacao->data['fk_id_remetente']);
        $this->assertSame(0, $this->remetente->notifications()->count());

        Sanctum::actingAs($this->destinatario);
        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.tipo', 'MANUAL')
            ->assertJsonPath('data.0.titulo', 'Hora de estudar')
            ->assertJsonPath('data.0.texto', 'Não esqueça do seu hábito de hoje.');
    }

    public function test_job_dispara_o_push_pelo_fcm(): void
    {
        config(['notifications.push_ativo' => true]);
        $this->destinatario->forceFill(['token_dispositivo' => 'token-a'])->save();
        $mensageria = Mockery::mock(Messaging::class);
        $mensageria->shouldReceive('sendMulticast')->once()
            ->withArgs(fn (FcmMessage $mensagem, array $tokens) => $tokens === ['token-a']
                && $mensagem->notification->title === 'Hora de estudar'
                && $mensagem->notification->body === 'Não esqueça do seu hábito de hoje.'
                && $mensagem->data['tipo'] === 'MANUAL')
            ->andReturn(MulticastSendReport::withItems([
                SendReport::success(MessageTarget::with(MessageTarget::TOKEN, 'token-a'), ['name' => 'mensagem']),
            ]));
        $this->app->instance(Messaging::class, $mensageria);

        $this->assertTrue($this->enviar());
    }

    public function test_sem_token_fica_so_no_historico(): void
    {
        config(['notifications.push_ativo' => true]);
        $mensageria = Mockery::mock(Messaging::class);
        $mensageria->shouldNotReceive('sendMulticast');
        $this->app->instance(Messaging::class, $mensageria);

        $this->assertTrue($this->enviar());
        $this->assertSame(1, $this->destinatario->notifications()->count());
    }

    public function test_falha_do_firebase_mantem_o_historico(): void
    {
        config(['notifications.push_ativo' => true]);
        $this->destinatario->forceFill(['token_dispositivo' => 'token-a'])->save();
        $mensageria = Mockery::mock(Messaging::class);
        $mensageria->shouldReceive('sendMulticast')->once()->andThrow(new ServerUnavailable('indisponível'));
        $this->app->instance(Messaging::class, $mensageria);

        $this->assertTrue($this->enviar());
        $this->assertSame(1, $this->destinatario->notifications()->count());
    }

    public function test_job_ignora_destinatario_excluido(): void
    {
        $id = $this->destinatario->id;
        $this->destinatario->delete();

        $this->assertFalse(app(NotificacaoService::class)->enviarNotificacaoManual($id, $this->remetente->id, 'Título', 'Texto'));
    }
}
