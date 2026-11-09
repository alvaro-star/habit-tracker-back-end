<?php

namespace Tests\Feature\Notifications;

use App\Models\ConfigNotificacao;
use App\Models\GatilhoNotificacao;
use App\Models\User;
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
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.ativo', true)
            ->assertJsonPath('data.0.horario', null);
    }

    public function test_salva_e_substitui_a_configuracao(): void
    {
        $url = "/api/notifications/settings/{$this->gatilho->id}";

        $this->putJson($url, ['ativo' => false, 'horario' => '21:30'])
            ->assertOk()
            ->assertJsonPath('data.ativo', false)
            ->assertJsonPath('data.horario', '21:30')
            ->assertJsonPath('data.tipo', 'STREAK_EM_RISCO');

        $this->putJson($url, ['ativo' => true])
            ->assertOk()
            ->assertJsonPath('data.ativo', true)
            ->assertJsonPath('data.horario', null);

        $this->assertSame(1, ConfigNotificacao::where('fk_id_usuario', $this->usuario->id)->count());
    }

    public function test_nao_afeta_outro_usuario(): void
    {
        $this->putJson("/api/notifications/settings/{$this->gatilho->id}", ['ativo' => false])->assertOk();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/notifications/settings')->assertJsonPath('data.0.ativo', true);
    }

    public function test_valida_os_campos(): void
    {
        $this->putJson("/api/notifications/settings/{$this->gatilho->id}", ['horario' => '25:00'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ativo', 'horario']);
    }

    public function test_gatilho_inexistente_devolve_404(): void
    {
        $this->putJson('/api/notifications/settings/999', ['ativo' => true])->assertNotFound();
    }
}
