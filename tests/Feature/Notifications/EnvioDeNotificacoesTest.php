<?php

namespace Tests\Feature\Notifications;

use App\Enums\TipoGatilho;
use App\Jobs\EnviarAvisoDeInatividade;
use App\Models\ConfigNotificacao;
use App\Models\GatilhoNotificacao;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Services\NotificacaoService;
use App\Services\StreakService;
use Carbon\Carbon;
use Database\Seeders\GatilhoNotificacaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnvioDeNotificacoesTest extends TestCase
{
    use RefreshDatabase;

    private NotificacaoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-08 23:30:00');
        $this->seed(GatilhoNotificacaoSeeder::class);
        $this->service = app(NotificacaoService::class);
    }

    private function usuario(int $streak = 0, ?Carbon $ultimoAcesso = null): User
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['streak_atual' => $streak, 'ultimo_acesso' => $ultimoAcesso ?? now()])->save();

        return $usuario;
    }

    private function concluir(User $usuario, string ...$datas): void
    {
        $categoria = DB::table('categoria')->insertGetId(['nome' => 'Saúde'.$usuario->id]);
        $habito = Habito::create([
            'fk_id_usuario' => $usuario->id, 'fk_id_categoria' => $categoria, 'nome' => 'Ler',
            'polaridade' => 'BOM', 'tipo_meta' => 'REALIZACAO', 'frequencia' => 'DIARIA',
            'dificuldade' => 'FACIL', 'peso' => 1,
        ]);

        foreach ($datas as $data) {
            RegistroHabito::create(['fk_id_habito' => $habito->id, 'data' => $data, 'status' => 'CONCLUIDO']);
        }
    }

    private function configurar(User $usuario, string $tipo, bool $ativo, ?string $horario = null): void
    {
        ConfigNotificacao::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_gatilho' => GatilhoNotificacao::where('tipo', $tipo)->value('id'),
            'ativo' => $ativo,
            'horario' => $horario,
        ]);
    }

    public function test_calcula_a_sequencia_contando_ate_ontem(): void
    {
        $usuario = $this->usuario();
        $this->concluir($usuario, '2026-11-07', '2026-11-06', '2026-11-04');

        $this->assertSame(2, app(StreakService::class)->atualizar($usuario->id));
        $this->assertSame(2, $usuario->refresh()->streak_atual);
    }

    public function test_avisa_quem_nao_fez_todo_o_xp_a_cada_revisao(): void
    {
        Carbon::setTestNow('2026-11-08 12:00:00');
        $usuario = $this->usuario(1);
        $this->concluir($usuario, '2026-11-07');

        $this->assertSame(1, $this->service->enviarAvisosDeStreak());
        $this->assertSame(0, $this->service->enviarAvisosDeStreak());

        Carbon::setTestNow('2026-11-08 16:00:00');
        $this->assertSame(1, $this->service->enviarAvisosDeStreak());

        $notificacao = $usuario->notifications()->first();
        $this->assertSame('STREAK_EM_RISCO', $notificacao->type);
        $this->assertSame('Você ainda não cumpriu todos os hábitos de hoje. Complete-os para ganhar todo o XP do dia!', $notificacao->data['texto']);
    }

    public function test_avisa_quem_concluiu_so_parte_do_xp_do_dia(): void
    {
        $usuario = $this->usuario(2);
        $this->concluir($usuario, '2026-11-07', '2026-11-08');
        Habito::create([
            'fk_id_usuario' => $usuario->id, 'fk_id_categoria' => Habito::query()->value('fk_id_categoria'), 'nome' => 'Correr',
            'polaridade' => 'BOM', 'tipo_meta' => 'REALIZACAO', 'frequencia' => 'DIARIA',
            'dificuldade' => 'FACIL', 'peso' => 1,
        ]);

        $this->assertSame(1, $this->service->enviarAvisosDeStreak());
    }

    public function test_nao_avisa_quem_fez_todo_o_xp_do_dia(): void
    {
        $usuario = $this->usuario(2);
        $this->concluir($usuario, '2026-11-07', '2026-11-08');

        $this->assertSame(0, $this->service->enviarAvisosDeStreak());
    }

    public function test_nao_avisa_quem_nao_tem_habitos_no_dia(): void
    {
        $usuario = $this->usuario(1);
        $this->concluir($usuario, '2026-11-07');
        Habito::query()->update(['frequencia' => 'SEMANAL', 'dia_frequencia' => 'SAB']);

        $this->assertSame(0, $this->service->enviarAvisosDeStreak());
        $this->assertSame(1, $usuario->refresh()->streak_atual);
    }

    public function test_nao_avisa_antes_do_horario_padrao(): void
    {
        Carbon::setTestNow('2026-11-08 04:00:00');
        $usuario = $this->usuario(1);
        $this->concluir($usuario, '2026-11-07');

        $this->assertSame(0, $this->service->enviarAvisosDeStreak());

        Carbon::setTestNow('2026-11-08 08:00:00');
        $this->assertSame(1, $this->service->enviarAvisosDeStreak());
    }

    public function test_respeita_o_horario_configurado(): void
    {
        Carbon::setTestNow('2026-11-08 16:00:00');
        $usuario = $this->usuario(1);
        $this->concluir($usuario, '2026-11-07');
        $this->configurar($usuario, 'STREAK_EM_RISCO', true, '17:00');

        $this->assertSame(0, $this->service->enviarAvisosDeStreak());

        Carbon::setTestNow('2026-11-08 20:00:00');
        $this->assertSame(1, $this->service->enviarAvisosDeStreak());
    }

    public function test_nao_avisa_streak_com_gatilho_desativado(): void
    {
        $usuario = $this->usuario(1);
        $this->concluir($usuario, '2026-11-07');
        $this->configurar($usuario, 'STREAK_EM_RISCO', false);

        $this->assertSame(0, $this->service->enviarAvisosDeStreak());
    }

    public function test_zera_sequencia_perdida_sem_avisar(): void
    {
        $usuario = $this->usuario(3);
        $this->concluir($usuario, '2026-11-01');

        $this->assertSame(0, $this->service->enviarAvisosDeStreak());
        $this->assertSame(0, $usuario->refresh()->streak_atual);
    }

    public function test_agenda_no_momento_em_que_completa_3_ou_7_dias(): void
    {
        Carbon::setTestNow('2026-11-08 00:00:00');
        Queue::fake();
        $tres = $this->usuario(0, Carbon::parse('2026-11-05 14:10:00'));
        $sete = $this->usuario(0, Carbon::parse('2026-11-01 09:30:00'));
        $this->usuario(0, Carbon::parse('2026-11-06 14:00:00'));
        $this->usuario(0, Carbon::parse('2026-11-04 23:59:59'));
        $this->usuario(0, Carbon::parse('2026-10-31 23:59:59'));

        $this->assertSame(2, $this->service->agendarAvisosDeInatividade());

        Queue::assertPushed(EnviarAvisoDeInatividade::class, 2);
        Queue::assertPushed(EnviarAvisoDeInatividade::class, fn (EnviarAvisoDeInatividade $job) => $job->usuarioId === $tres->id
            && $job->tipo === TipoGatilho::INATIVIDADE_3_DIAS
            && $job->delay->format('Y-m-d H:i:s') === '2026-11-08 14:10:00');
        Queue::assertPushed(EnviarAvisoDeInatividade::class, fn (EnviarAvisoDeInatividade $job) => $job->usuarioId === $sete->id
            && $job->tipo === TipoGatilho::INATIVIDADE_7_DIAS
            && $job->delay->format('Y-m-d H:i:s') === '2026-11-08 09:30:00');
    }

    public function test_usuario_que_nunca_acessou_conta_desde_o_cadastro(): void
    {
        Carbon::setTestNow('2026-11-08 00:00:00');
        Queue::fake();
        $usuario = User::factory()->create(['created_at' => '2026-11-05 08:00:00']);

        $this->assertSame(1, $this->service->agendarAvisosDeInatividade());
        Queue::assertPushed(EnviarAvisoDeInatividade::class, fn (EnviarAvisoDeInatividade $job) => $job->usuarioId === $usuario->id
            && $job->ultimoAcesso === '2026-11-05 08:00:00');
    }

    public function test_job_de_inatividade_envia_uma_vez(): void
    {
        $usuario = $this->usuario(0, Carbon::parse('2026-11-05 23:30:00'));

        $this->assertTrue($this->service->enviarAvisoDeInatividade($usuario->id, TipoGatilho::INATIVIDADE_3_DIAS, '2026-11-05 23:30:00'));
        $this->assertFalse($this->service->enviarAvisoDeInatividade($usuario->id, TipoGatilho::INATIVIDADE_3_DIAS, '2026-11-05 23:30:00'));
        $this->assertSame(['INATIVIDADE_3_DIAS'], $usuario->notifications()->pluck('type')->all());
    }

    public function test_job_de_inatividade_nao_envia_se_o_usuario_voltou(): void
    {
        $usuario = $this->usuario(0, Carbon::parse('2026-11-05 23:30:00'));
        $usuario->forceFill(['ultimo_acesso' => '2026-11-08 10:00:00'])->save();

        $this->assertFalse($this->service->enviarAvisoDeInatividade($usuario->id, TipoGatilho::INATIVIDADE_3_DIAS, '2026-11-05 23:30:00'));
        $this->assertSame(0, $usuario->notifications()->count());
    }

    public function test_job_de_inatividade_respeita_gatilho_desativado_e_usuario_excluido(): void
    {
        $usuario = $this->usuario(0, Carbon::parse('2026-11-05 23:30:00'));
        $this->configurar($usuario, 'INATIVIDADE_3_DIAS', false);

        $this->assertFalse($this->service->enviarAvisoDeInatividade($usuario->id, TipoGatilho::INATIVIDADE_3_DIAS, '2026-11-05 23:30:00'));
        $this->assertFalse($this->service->enviarAvisoDeInatividade($usuario->id + 100, TipoGatilho::INATIVIDADE_3_DIAS, '2026-11-05 23:30:00'));
    }

    public function test_requisicao_autenticada_registra_o_ultimo_acesso(): void
    {
        $usuario = $this->usuario(0, now()->subDays(1));
        Sanctum::actingAs($usuario);

        $this->getJson('/api/notifications')->assertOk();

        $this->assertTrue($usuario->refresh()->ultimo_acesso->equalTo(now()));
    }

    public function test_lista_apenas_as_notificacoes_do_usuario(): void
    {
        $usuario = $this->usuario(0, now()->subDays(3));
        $this->usuario(0, now()->subDays(3));
        $this->service->enviarAvisoDeInatividade($usuario->id, TipoGatilho::INATIVIDADE_3_DIAS, $usuario->ultimo_acesso->format('Y-m-d H:i:s'));
        Sanctum::actingAs($usuario);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tipo', 'INATIVIDADE_3_DIAS')
            ->assertJsonPath('data.0.canal', 'PUSH')
            ->assertJsonPath('data.0.texto', 'Seu dragão está com saudades! Volte e continue sua jornada.');
    }
}
