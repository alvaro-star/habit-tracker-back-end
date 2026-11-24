<?php

namespace Tests\Feature\Notifications;

use App\Enums\TipoGatilho;
use App\Jobs\EnviarLembreteDeAcesso;
use App\Models\ConfigNotificacao;
use App\Models\GatilhoNotificacao;
use App\Models\User;
use App\Services\NotificacaoService;
use Carbon\Carbon;
use Database\Seeders\GatilhoNotificacaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LembreteDeAcessoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-09 18:00:00');
        config(['notifications.push_ativo' => false]);
        $this->seed(GatilhoNotificacaoSeeder::class);
        $this->usuario = $this->usuarioCadastradoOntem();
    }

    /** @param array<string, mixed> $colunas */
    private function usuarioCadastradoOntem(array $colunas = []): User
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['created_at' => '2026-11-08 10:00:00', ...$colunas])->save();

        return $usuario;
    }

    private function agendar(): int
    {
        Queue::fake();

        return app(NotificacaoService::class)->agendarLembretesDeAcesso();
    }

    private function enviar(string $data = '2026-11-09'): bool
    {
        return app(NotificacaoService::class)->enviarLembreteDeAcesso($this->usuario->id, $data);
    }

    public function test_despacha_job_para_quem_nao_acessou_hoje(): void
    {
        $this->usuario->forceFill(['ultimo_acesso' => '2026-11-08 20:00:00'])->save();

        $this->assertSame(1, $this->agendar());

        Queue::assertPushed(EnviarLembreteDeAcesso::class, fn ($job) => $job->usuarioId === $this->usuario->id && $job->data === '2026-11-09');
    }

    public function test_nao_despacha_para_quem_ja_acessou_ou_fez_login_hoje_ou_se_cadastrou_hoje(): void
    {
        $this->usuario->forceFill(['ultimo_acesso' => '2026-11-09 07:00:00'])->save();
        $this->usuarioCadastradoOntem(['ultimo_acesso_at' => '2026-11-09 07:00:00']);
        User::factory()->create();

        $this->assertSame(0, $this->agendar());
    }

    public function test_respeita_o_horario_padrao_e_o_configurado(): void
    {
        Carbon::setTestNow('2026-11-09 17:30:00');
        $this->assertSame(0, $this->agendar());

        ConfigNotificacao::create([
            'fk_id_usuario' => $this->usuario->id,
            'fk_id_gatilho' => GatilhoNotificacao::where('tipo', TipoGatilho::LEMBRETE_ACESSO->value)->value('id'),
            'ativo' => true,
            'horario' => '09:00',
        ]);

        $this->assertSame(1, $this->agendar());
    }

    public function test_nao_despacha_com_o_gatilho_desativado(): void
    {
        ConfigNotificacao::create([
            'fk_id_usuario' => $this->usuario->id,
            'fk_id_gatilho' => GatilhoNotificacao::where('tipo', TipoGatilho::LEMBRETE_ACESSO->value)->value('id'),
            'ativo' => false,
        ]);

        $this->assertSame(0, $this->agendar());
    }

    public function test_envia_um_unico_lembrete_por_dia(): void
    {
        $this->assertTrue($this->enviar());
        $this->assertFalse($this->enviar());

        $this->assertSame(1, $this->usuario->notifications()->where('type', TipoGatilho::LEMBRETE_ACESSO->value)->count());
        Carbon::setTestNow('2026-11-09 18:30:00');
        $this->assertSame(0, $this->agendar());

        Carbon::setTestNow('2026-11-10 18:00:00');
        $this->assertSame(1, $this->agendar());
    }

    public function test_job_nao_envia_se_o_usuario_entrou_depois_do_despacho(): void
    {
        $this->usuario->forceFill(['ultimo_acesso' => '2026-11-09 18:00:00'])->save();

        $this->assertFalse($this->enviar());
        $this->assertSame(0, $this->usuario->notifications()->count());
    }

    public function test_job_atrasado_para_o_dia_seguinte_nao_envia(): void
    {
        Carbon::setTestNow('2026-11-10 00:10:00');

        $this->assertFalse($this->enviar('2026-11-09'));
        $this->assertSame(0, $this->usuario->notifications()->count());
    }
}
