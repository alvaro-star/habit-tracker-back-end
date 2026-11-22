<?php

namespace Tests\Feature\Notifications;

use App\DTOs\Notifications\ConfigNotificacaoDTO;
use App\Models\ConfigNotificacao;
use App\Models\GatilhoNotificacao;
use App\Models\User;
use App\Services\ConfigNotificacaoService;
use Database\Seeders\GatilhoNotificacaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConfigNotificacaoApiTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private GatilhoNotificacao $gatilho;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GatilhoNotificacaoSeeder::class);
        $this->gatilho = GatilhoNotificacao::where('tipo', 'STREAK_EM_RISCO')->firstOrFail();
        $this->usuario = User::factory()->create();
        Sanctum::actingAs($this->usuario);
    }

    public function test_exige_autenticacao(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/notifications/settings')->assertUnauthorized();
    }

    public function test_gatilho_sem_configuracao_vem_ativo(): void
    {
        $this->getJson('/api/notifications/settings')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.ativo', true)
            ->assertJsonPath('data.0.horario', null);
    }

    public function test_lista_a_configuracao_salva_so_do_proprio_usuario(): void
    {
        app(ConfigNotificacaoService::class)->salvar(
            $this->usuario->id,
            $this->gatilho->id,
            new ConfigNotificacaoDTO(false, '21:30'),
        );

        $configuracao = collect($this->getJson('/api/notifications/settings')->assertOk()->json('data'))
            ->firstWhere('tipo', 'STREAK_EM_RISCO');
        $this->assertFalse($configuracao['ativo']);
        $this->assertSame('21:30', $configuracao['horario']);
        $this->assertSame(1, ConfigNotificacao::where('fk_id_usuario', $this->usuario->id)->count());

        Sanctum::actingAs(User::factory()->create());

        $outro = collect($this->getJson('/api/notifications/settings')->json('data'))
            ->firstWhere('tipo', 'STREAK_EM_RISCO');
        $this->assertTrue($outro['ativo']);
    }

    public function test_put_de_configuracao_nao_esta_disponivel(): void
    {
        $this->putJson("/api/notifications/settings/{$this->gatilho->id}", ['ativo' => false])->assertNotFound();

        $this->assertSame(0, ConfigNotificacao::where('fk_id_usuario', $this->usuario->id)->count());
    }
}
