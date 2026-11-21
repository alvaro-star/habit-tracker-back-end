<?php

namespace Tests\Feature\Notifications;

use App\Enums\TipoGatilho;
use App\Jobs\EnviarLembreteDeHabito;
use App\Models\ConfigNotificacao;
use App\Models\GatilhoNotificacao;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Services\NotificacaoService;
use Carbon\Carbon;
use Database\Seeders\GatilhoNotificacaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use NotificationChannels\Fcm\FcmMessage;
use Tests\TestCase;

class LembreteDeHabitoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private int $categoria;

    protected function setUp(): void
    {
        parent::setUp();
        // Segunda-feira, 07:00 em Campo Grande.
        Carbon::setTestNow('2026-11-09 07:00:00');
        config(['notifications.push_ativo' => false]);
        $this->seed(GatilhoNotificacaoSeeder::class);
        $this->usuario = User::factory()->create();
        $this->categoria = DB::table('categoria')->insertGetId(['nome' => 'Saúde']);
    }

    /** @param array<string, mixed> $dados */
    private function habito(array $dados = []): Habito
    {
        return Habito::create([
            'fk_id_usuario' => $this->usuario->id, 'fk_id_categoria' => $this->categoria, 'nome' => 'Ler',
            'polaridade' => 'BOM', 'tipo_meta' => 'REALIZACAO', 'frequencia' => 'DIARIA',
            'dificuldade' => 'FACIL', 'peso' => 1, 'ativo' => true, 'horario_lembrete' => '07:25',
            ...$dados,
        ]);
    }

    private function agendar(): int
    {
        Queue::fake();

        return app(NotificacaoService::class)->agendarLembretesDeHabitos();
    }

    private function job(Habito $habito, string $data = '2026-11-09', string $horario = '07:25'): EnviarLembreteDeHabito
    {
        return new EnviarLembreteDeHabito($habito->id, $habito->fk_id_usuario, $data, $horario);
    }

    private function executar(EnviarLembreteDeHabito $job): void
    {
        $job->handle(app(NotificacaoService::class));
    }

    public function test_cron_das_7_agenda_de_7h00_ate_antes_das_7h30_no_horario_exato(): void
    {
        $this->habito(['horario_lembrete' => '06:59']);
        $this->habito(['horario_lembrete' => '07:00']);
        $vinteECinco = $this->habito(['horario_lembrete' => '07:25']);
        $this->habito(['horario_lembrete' => '07:29']);
        $this->habito(['horario_lembrete' => '07:30']);

        $this->assertSame(3, $this->agendar());

        Queue::assertPushed(EnviarLembreteDeHabito::class, 3);
        Queue::assertPushed(EnviarLembreteDeHabito::class, fn (EnviarLembreteDeHabito $job) => $job->habitoId === $vinteECinco->id
            && $job->horario === '07:25'
            && $job->delay->format('Y-m-d H:i:s') === '2026-11-09 07:25:00');
    }

    public function test_cron_das_7h30_agenda_ate_antes_das_8h(): void
    {
        Carbon::setTestNow('2026-11-09 07:30:00');
        $this->habito(['horario_lembrete' => '07:29']);
        $this->habito(['horario_lembrete' => '07:30']);
        $this->habito(['horario_lembrete' => '07:59']);
        $this->habito(['horario_lembrete' => '08:00']);

        $this->assertSame(2, $this->agendar());
    }

    public function test_cron_atrasado_continua_no_mesmo_bloco(): void
    {
        Carbon::setTestNow('2026-11-09 07:02:00');
        $this->habito(['horario_lembrete' => '07:00']);
        $this->habito(['horario_lembrete' => '07:29']);

        $this->assertSame(2, $this->agendar());
    }

    public function test_ultimo_bloco_agenda_ate_23h59_do_mesmo_dia(): void
    {
        Carbon::setTestNow('2026-11-09 23:30:00');
        $this->habito(['horario_lembrete' => '23:59']);
        $this->habito(['horario_lembrete' => '00:00']);

        $this->assertSame(1, $this->agendar());
        Queue::assertPushed(EnviarLembreteDeHabito::class, fn (EnviarLembreteDeHabito $job) => $job->data === '2026-11-09'
            && $job->delay->format('Y-m-d H:i') === '2026-11-09 23:59');
    }

    public function test_horario_e_de_campo_grande_e_nao_utc(): void
    {
        $this->habito(['horario_lembrete' => '07:25']);
        Carbon::setTestNow(Carbon::parse('2026-11-09 07:00:00', 'UTC'));

        $this->assertSame(0, $this->agendar());
    }

    public function test_nao_agenda_habito_ruim_inativo_ou_ja_concluido(): void
    {
        $this->habito(['polaridade' => 'RUIM']);
        $this->habito(['ativo' => false]);
        $concluido = $this->habito();
        RegistroHabito::create([
            'fk_id_habito' => $concluido->id, 'data' => '2026-11-09', 'status' => 'CONCLUIDO', 'percentual_cumprido' => 100,
        ]);

        $this->assertSame(0, $this->agendar());
    }

    public function test_respeita_a_frequencia(): void
    {
        $this->habito(['frequencia' => 'SEMANAL', 'dia_frequencia' => 'SEG']);
        $this->habito(['frequencia' => 'SEMANAL', 'dia_frequencia' => 'TER']);
        $this->habito(['frequencia' => 'MENSAL', 'dia_frequencia' => '9']);
        $this->habito(['frequencia' => 'MENSAL', 'dia_frequencia' => '10']);

        $this->assertSame(2, $this->agendar());
    }

    public function test_job_envia_com_o_nome_do_habito_uma_vez_por_dia(): void
    {
        $habito = $this->habito();
        Carbon::setTestNow('2026-11-09 07:25:00');

        $this->executar($this->job($habito));
        $this->executar($this->job($habito));

        $notificacao = $this->usuario->notifications()->sole();
        $this->assertSame(TipoGatilho::LEMBRETE_DIARIO->value, $notificacao->type);
        $this->assertSame('Hora de Ler! Seu dragão está contando com você.', $notificacao->data['texto']);
        $this->assertSame($habito->id, $notificacao->data['fk_id_habito']);

        Carbon::setTestNow('2026-11-10 07:25:00');
        $this->executar($this->job($habito, '2026-11-10'));
        $this->assertSame(2, $this->usuario->notifications()->count());
    }

    public function test_job_nao_envia_se_o_habito_mudou_depois_do_agendamento(): void
    {
        $outroHorario = $this->habito();
        $desativado = $this->habito();
        $concluido = $this->habito();
        $ruim = $this->habito();

        $outroHorario->update(['horario_lembrete' => '07:28']);
        $desativado->update(['ativo' => false]);
        $ruim->update(['polaridade' => 'RUIM']);
        RegistroHabito::create([
            'fk_id_habito' => $concluido->id, 'data' => '2026-11-09', 'status' => 'CONCLUIDO', 'percentual_cumprido' => 100,
        ]);

        foreach ([$outroHorario, $desativado, $concluido, $ruim] as $habito) {
            $this->executar($this->job($habito));
        }

        $this->assertSame(0, $this->usuario->notifications()->count());
    }

    public function test_job_nao_envia_para_habito_excluido_nem_de_outro_usuario(): void
    {
        $habito = $this->habito();

        $this->executar(new EnviarLembreteDeHabito($habito->id + 100, $habito->fk_id_usuario, '2026-11-09', '07:25'));
        $this->executar(new EnviarLembreteDeHabito($habito->id, $habito->fk_id_usuario + 100, '2026-11-09', '07:25'));

        $this->assertSame(0, $this->usuario->notifications()->count());
    }

    public function test_job_respeita_gatilho_desativado_pelo_usuario(): void
    {
        $habito = $this->habito();
        ConfigNotificacao::create([
            'fk_id_usuario' => $this->usuario->id,
            'fk_id_gatilho' => GatilhoNotificacao::where('tipo', TipoGatilho::LEMBRETE_DIARIO->value)->value('id'),
            'ativo' => false,
        ]);

        $this->executar($this->job($habito));

        $this->assertSame(0, $this->usuario->notifications()->count());
    }

    public function test_job_dispara_o_push_com_o_habito_no_titulo(): void
    {
        config(['notifications.push_ativo' => true]);
        $this->usuario->forceFill(['token_dispositivo' => 'token-a'])->save();
        $habito = $this->habito();
        $mensageria = Mockery::mock(Messaging::class);
        $mensageria->shouldReceive('sendMulticast')->once()
            ->withArgs(fn (FcmMessage $mensagem, array $tokens) => $tokens === ['token-a']
                && $mensagem->notification->title === 'Ler'
                && $mensagem->notification->body === 'Hora de Ler! Seu dragão está contando com você.'
                && $mensagem->data['fk_id_habito'] === (string) $habito->id)
            ->andReturn(MulticastSendReport::withItems([
                SendReport::success(MessageTarget::with(MessageTarget::TOKEN, 'token-a'), ['name' => 'mensagem']),
            ]));
        $this->app->instance(Messaging::class, $mensageria);

        $this->executar($this->job($habito));

        $this->assertSame(1, $this->usuario->notifications()->count());
    }
}
