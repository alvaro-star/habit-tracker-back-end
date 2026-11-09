<?php

namespace Tests\Feature\Notifications;

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

    public function test_avisa_streak_em_risco_uma_vez_por_dia(): void
    {
        $usuario = $this->usuario(1);
        $this->concluir($usuario, '2026-11-07');

        $this->assertSame(1, $this->service->enviarAvisosDeStreak());
        $this->assertSame(0, $this->service->enviarAvisosDeStreak());
        $this->assertSame('STREAK_EM_RISCO', $usuario->notifications()->first()->type);
    }

    public function test_nao_avisa_quem_concluiu_hoje(): void
    {
        $usuario = $this->usuario(2);
        $this->concluir($usuario, '2026-11-07', '2026-11-08');

        $this->assertSame(0, $this->service->enviarAvisosDeStreak());
    }

    public function test_nao_avisa_antes_do_horario_padrao(): void
    {
        Carbon::setTestNow('2026-11-08 22:00:00');
        $usuario = $this->usuario(1);
        $this->concluir($usuario, '2026-11-07');

        $this->assertSame(0, $this->service->enviarAvisosDeStreak());
    }

    public function test_respeita_o_horario_configurado(): void
    {
        Carbon::setTestNow('2026-11-08 18:00:00');
        $usuario = $this->usuario(1);
        $this->concluir($usuario, '2026-11-07');
        $this->configurar($usuario, 'STREAK_EM_RISCO', true, '17:00');

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

    public function test_reengaja_com_3_e_depois_7_dias_sem_repetir(): void
    {
        $usuario = $this->usuario(0, now()->subDays(3));

        $this->assertSame(1, $this->service->enviarReengajamento());
        $this->assertSame(0, $this->service->enviarReengajamento());

        Carbon::setTestNow(now()->addDays(4));

        $this->assertSame(1, $this->service->enviarReengajamento());
        $this->assertEqualsCanonicalizing(
            ['INATIVIDADE_3_DIAS', 'INATIVIDADE_7_DIAS'],
            $usuario->notifications()->pluck('type')->all(),
        );
    }

    public function test_quem_passou_de_7_dias_recebe_so_o_aviso_longo(): void
    {
        $usuario = $this->usuario(0, now()->subDays(10));

        $this->assertSame(1, $this->service->enviarReengajamento());
        $this->assertSame(['INATIVIDADE_7_DIAS'], $usuario->notifications()->pluck('type')->all());
    }

    public function test_nao_reengaja_usuario_ativo_nem_com_gatilho_desativado(): void
    {
        $this->usuario(0, now()->subDays(2));
        $this->configurar($this->usuario(0, now()->subDays(4)), 'INATIVIDADE_3_DIAS', false);

        $this->assertSame(0, $this->service->enviarReengajamento());
    }

    public function test_usuario_que_nunca_acessou_conta_desde_o_cadastro(): void
    {
        $usuario = User::factory()->create(['created_at' => now()->subDays(4)]);

        $this->assertSame(1, $this->service->enviarReengajamento());
        $this->assertSame('INATIVIDADE_3_DIAS', $usuario->notifications()->first()->type);
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
        $this->service->enviarReengajamento();
        Sanctum::actingAs($usuario);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tipo', 'INATIVIDADE_3_DIAS')
            ->assertJsonPath('data.0.canal', 'PUSH')
            ->assertJsonPath('data.0.texto', 'Seu dragão está com saudades! Volte e continue sua jornada.');
    }
}
