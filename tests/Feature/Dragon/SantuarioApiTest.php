<?php

namespace Tests\Feature\Dragon;

use App\Models\Dragao;
use App\Models\EscolhaElemento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SantuarioApiTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-12 10:00:00');
        $this->usuario = User::factory()->create();
        Sanctum::actingAs($this->usuario);
    }

    /** @param array<string, mixed> $extra */
    private function dragaoDe(User $usuario, array $extra = []): Dragao
    {
        return Dragao::create(array_merge([
            'fk_id_usuario' => $usuario->id,
            'nome' => 'Smaug',
            'xp_ciclo' => 9000,
            'status' => 'SANTUARIO',
            'elemento' => 'FOGO',
            'data_nascimento' => '2025-11-01',
            'data_termino' => '2026-10-27',
            'tempo_total_dias' => 360,
            'arte_final_url' => 'https://exemplo.test/smaug.png',
        ], $extra));
    }

    public function test_rotas_exigem_autenticacao(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/sanctuary')->assertUnauthorized();
        $this->getJson('/api/sanctuary/1')->assertUnauthorized();
    }

    public function test_lista_so_os_dragoes_do_santuario_do_mais_recente_para_o_mais_antigo(): void
    {
        $antigo = $this->dragaoDe($this->usuario, ['nome' => 'Antigo', 'data_termino' => '2025-01-10']);
        $recente = $this->dragaoDe($this->usuario, ['nome' => 'Recente', 'data_termino' => '2026-10-27']);
        $this->dragaoDe($this->usuario, ['nome' => 'Ativo', 'status' => 'ATIVO', 'data_termino' => null]);
        $this->dragaoDe(User::factory()->create(), ['nome' => 'Alheio']);

        $this->getJson('/api/sanctuary')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $recente->id)
            ->assertJsonPath('data.1.id', $antigo->id);
    }

    public function test_entrada_traz_nome_arte_periodo_trajetoria_e_tempo_total(): void
    {
        $dragao = $this->dragaoDe($this->usuario);
        // Gravadas fora de ordem: a trajetória sai na ordem em que os elementos foram escolhidos.
        EscolhaElemento::create(['fk_id_dragao' => $dragao->id, 'elemento' => 'FOGO', 'estagio' => 'ADULTO', 'data_escolha' => '2026-08-01 10:00:00']);
        EscolhaElemento::create(['fk_id_dragao' => $dragao->id, 'elemento' => 'AGUA', 'estagio' => 'ADULTO', 'data_escolha' => '2026-07-01 10:00:00']);

        $this->getJson("/api/sanctuary/{$dragao->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $dragao->id)
            ->assertJsonPath('data.nome', 'Smaug')
            ->assertJsonPath('data.arte_final_url', 'https://exemplo.test/smaug.png')
            ->assertJsonPath('data.elemento', 'FOGO')
            ->assertJsonPath('data.data_nascimento', '2025-11-01')
            ->assertJsonPath('data.data_termino', '2026-10-27')
            ->assertJsonPath('data.tempo_total_dias', 360)
            ->assertJsonCount(2, 'data.trajetoria')
            ->assertJsonPath('data.trajetoria.0.elemento', 'AGUA')
            ->assertJsonPath('data.trajetoria.0.estagio', 'ADULTO')
            ->assertJsonPath('data.trajetoria.1.elemento', 'FOGO');

        $this->getJson('/api/sanctuary')->assertOk()->assertJsonCount(2, 'data.0.trajetoria');
    }

    public function test_dragao_ativo_ou_de_outro_usuario_nao_e_entrada_do_santuario(): void
    {
        $ativo = $this->dragaoDe($this->usuario, ['status' => 'ATIVO', 'data_termino' => null]);
        $alheio = $this->dragaoDe(User::factory()->create());

        $this->getJson("/api/sanctuary/{$ativo->id}")->assertNotFound();
        $this->getJson("/api/sanctuary/{$alheio->id}")->assertNotFound();
        $this->getJson('/api/sanctuary/abc')->assertNotFound();
    }

    public function test_aposentar_o_dragao_grava_o_termino_de_hoje_e_o_tempo_total(): void
    {
        $id = $this->postJson('/api/dragon/dragons', [
            'nome' => 'Smaug',
            'xp_ciclo' => 9000,
            'elemento' => 'FOGO',
            'status' => 'ATIVO',
            'data_nascimento' => '2026-11-02',
        ])->assertCreated()->assertJsonPath('data.data_termino', null)->json('data.id');

        $this->getJson('/api/sanctuary')->assertOk()->assertJsonCount(0, 'data');

        // O tempo total enviado é descartado: vem das datas.
        $this->patchJson("/api/dragon/dragons/{$id}", ['status' => 'SANTUARIO', 'tempo_total_dias' => 999])
            ->assertOk()
            ->assertJsonPath('data.data_termino', '2026-11-12')
            ->assertJsonPath('data.tempo_total_dias', 10);

        $this->getJson('/api/sanctuary')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.tempo_total_dias', 10)
            ->assertJsonPath('data.0.trajetoria.0.elemento', 'FOGO');
    }

    public function test_termino_enviado_define_o_tempo_total_e_nao_pode_ser_anterior_ao_nascimento(): void
    {
        $corpo = [
            'nome' => 'Antigo',
            'xp_ciclo' => 9000,
            'elemento' => 'TERRA',
            'status' => 'SANTUARIO',
            'data_nascimento' => '2026-01-01',
        ];

        $this->postJson('/api/dragon/dragons', [...$corpo, 'data_termino' => '2025-12-31'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['data_termino']);

        $id = $this->postJson('/api/dragon/dragons', [...$corpo, 'data_termino' => '2026-01-31'])
            ->assertCreated()
            ->assertJsonPath('data.tempo_total_dias', 30)
            ->json('data.id');

        // Mudar o término de um dragão do Santuário recalcula o tempo total.
        $this->patchJson("/api/dragon/dragons/{$id}", ['data_termino' => '2026-02-10'])
            ->assertOk()
            ->assertJsonPath('data.tempo_total_dias', 40);

        $this->patchJson("/api/dragon/dragons/{$id}", ['data_termino' => '2025-06-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['data_termino']);
    }

    public function test_dragao_do_santuario_nao_pode_ser_excluido(): void
    {
        $dragao = $this->dragaoDe($this->usuario);

        $this->deleteJson("/api/dragon/dragons/{$dragao->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);

        $this->assertDatabaseHas('dragao', ['id' => $dragao->id]);
        $this->getJson("/api/sanctuary/{$dragao->id}")->assertOk();
    }

    public function test_reativar_tira_o_dragao_do_santuario_e_limpa_o_termino(): void
    {
        $dragao = $this->dragaoDe($this->usuario);

        $this->patchJson("/api/dragon/dragons/{$dragao->id}", ['status' => 'ATIVO'])
            ->assertOk()
            ->assertJsonPath('data.data_termino', null)
            ->assertJsonPath('data.tempo_total_dias', null);

        $this->getJson('/api/sanctuary')->assertOk()->assertJsonCount(0, 'data');
        $this->deleteJson("/api/dragon/dragons/{$dragao->id}")->assertNoContent();
    }
}
