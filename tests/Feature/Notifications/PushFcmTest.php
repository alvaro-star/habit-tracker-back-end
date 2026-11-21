<?php

namespace Tests\Feature\Notifications;

use App\Enums\CanalNotificacao;
use App\Enums\TipoGatilho;
use App\Models\GatilhoNotificacao;
use App\Models\User;
use App\Services\NotificacaoService;
use Carbon\Carbon;
use Database\Seeders\GatilhoNotificacaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\Messaging\ServerUnavailable;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use NotificationChannels\Fcm\FcmMessage;
use Tests\TestCase;

class PushFcmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-08 23:30:00');
        config(['notifications.push_ativo' => true]);
        $this->seed(GatilhoNotificacaoSeeder::class);
    }

    private function usuarioInativo(?string $token = 'token-a'): User
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['ultimo_acesso' => now()->subDays(4), 'token_dispositivo' => $token])->save();

        return $usuario;
    }

    private function relatorioDeSucesso(string $token): MulticastSendReport
    {
        return MulticastSendReport::withItems([
            SendReport::success(MessageTarget::with(MessageTarget::TOKEN, $token), ['name' => 'mensagem']),
        ]);
    }

    public function test_login_grava_o_token_do_dispositivo(): void
    {
        $usuario = User::factory()->create(['password' => 'senha123']);

        $this->postJson('/api/auth/login', [
            'email' => $usuario->email, 'password' => 'senha123', 'token_dispositivo' => 'token-a',
        ])->assertOk()->assertJsonMissingPath('user.token_dispositivo');

        $this->assertSame('token-a', $usuario->fresh()->token_dispositivo);
    }

    public function test_login_com_token_nulo_mantem_o_anterior(): void
    {
        $usuario = User::factory()->create(['password' => 'senha123']);
        $usuario->forceFill(['token_dispositivo' => 'token-a'])->save();

        $this->postJson('/api/auth/login', [
            'email' => $usuario->email, 'password' => 'senha123', 'token_dispositivo' => null,
        ])->assertOk();

        $this->assertSame('token-a', $usuario->fresh()->token_dispositivo);
    }

    public function test_refresh_atualiza_o_token_ou_mantem_o_anterior_se_for_nulo(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['token_dispositivo' => 'token-a'])->save();

        $resposta = $this->withToken($usuario->createToken('refresh-token')->plainTextToken)
            ->postJson('/api/auth/refresh', ['token_dispositivo' => null])->assertOk();
        $this->assertSame('token-a', $usuario->fresh()->token_dispositivo);

        $this->app['auth']->forgetGuards();
        $this->withToken($resposta->json('refreshToken'))
            ->postJson('/api/auth/refresh', ['token_dispositivo' => 'token-b'])->assertOk();
        $this->assertSame('token-b', $usuario->fresh()->token_dispositivo);
    }

    public function test_token_passa_para_a_conta_que_entrou_no_mesmo_aparelho(): void
    {
        $anterior = $this->usuarioInativo('token-a');
        $novo = User::factory()->create(['password' => 'senha123']);

        $this->postJson('/api/auth/login', [
            'email' => $novo->email, 'password' => 'senha123', 'token_dispositivo' => 'token-a',
        ])->assertOk();

        $this->assertNull($anterior->fresh()->token_dispositivo);
        $this->assertSame('token-a', $novo->fresh()->token_dispositivo);
    }

    public function test_logout_remove_o_token_do_dispositivo(): void
    {
        $usuario = $this->usuarioInativo();

        $this->withToken($usuario->createToken('access-token')->plainTextToken)
            ->postJson('/api/auth/logout')->assertOk();

        $this->assertNull($usuario->fresh()->token_dispositivo);
    }

    public function test_gatilho_dispara_push_para_o_token_e_grava_no_banco(): void
    {
        $usuario = $this->usuarioInativo();
        $mensageria = Mockery::mock(Messaging::class);
        $mensageria->shouldReceive('sendMulticast')->once()
            ->withArgs(fn (FcmMessage $mensagem, array $tokens) => $tokens === ['token-a']
                && $mensagem->notification->body === GatilhoNotificacao::where('tipo', TipoGatilho::INATIVIDADE_3_DIAS->value)->value('texto_base'))
            ->andReturn($this->relatorioDeSucesso('token-a'));
        $this->app->instance(Messaging::class, $mensageria);

        $this->assertSame(1, app(NotificacaoService::class)->enviarReengajamento());
        $this->assertSame(1, $usuario->notifications()->count());
    }

    public function test_nao_dispara_push_sem_token_sem_credenciais_ou_em_gatilho_in_app(): void
    {
        $mensageria = Mockery::mock(Messaging::class);
        $mensageria->shouldNotReceive('sendMulticast');
        $this->app->instance(Messaging::class, $mensageria);

        $semToken = $this->usuarioInativo(null);
        app(NotificacaoService::class)->enviarReengajamento();
        $this->assertSame(1, $semToken->notifications()->count());

        $semCredenciais = $this->usuarioInativo('token-b');
        config(['notifications.push_ativo' => false]);
        app(NotificacaoService::class)->enviarReengajamento();
        $this->assertSame(1, $semCredenciais->notifications()->count());

        config(['notifications.push_ativo' => true]);
        GatilhoNotificacao::query()->update(['canal' => CanalNotificacao::IN_APP]);
        $inApp = $this->usuarioInativo('token-c');
        app(NotificacaoService::class)->enviarReengajamento();
        $this->assertSame(1, $inApp->notifications()->count());
    }

    public function test_token_nao_registrado_e_removido(): void
    {
        $usuario = $this->usuarioInativo();
        $mensageria = Mockery::mock(Messaging::class);
        $mensageria->shouldReceive('sendMulticast')->once()->andReturn(MulticastSendReport::withItems([
            SendReport::failure(MessageTarget::with(MessageTarget::TOKEN, 'token-a'), new NotFound('UNREGISTERED')),
        ]));
        $this->app->instance(Messaging::class, $mensageria);

        app(NotificacaoService::class)->enviarReengajamento();

        $this->assertNull($usuario->fresh()->token_dispositivo);
        $this->assertSame(1, $usuario->notifications()->count());
    }

    public function test_falha_do_firebase_nao_interrompe_os_demais_usuarios(): void
    {
        $primeiro = $this->usuarioInativo('token-a');
        $segundo = $this->usuarioInativo('token-b');
        $mensageria = Mockery::mock(Messaging::class);
        $mensageria->shouldReceive('sendMulticast')->twice()->andReturnUsing(
            fn () => throw new ServerUnavailable('indisponível'),
            fn () => $this->relatorioDeSucesso('token-b'),
        );
        $this->app->instance(Messaging::class, $mensageria);

        $this->assertSame(2, app(NotificacaoService::class)->enviarReengajamento());
        $this->assertSame('token-a', $primeiro->fresh()->token_dispositivo);
        $this->assertSame(1, $segundo->notifications()->count());
    }
}
