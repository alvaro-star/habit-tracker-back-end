<?php

namespace Tests\Feature\Progression;

use App\Models\Dragao;
use App\Models\Estagio;
use App\Models\Ovo;
use App\Models\User;
use App\Models\XpDiario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\VerificaCrudDaApi;
use Tests\TestCase;

class ProgressionApiTest extends TestCase
{
    use RefreshDatabase, VerificaCrudDaApi;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = User::factory()->create();
        Sanctum::actingAs($this->usuario);
    }

    private function dragaoDe(User $usuario): Dragao
    {
        return Dragao::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_ovo' => Ovo::create(['nome' => 'Ovo', 'imagem_url' => 'ovo.png'])->id,
            'fk_id_estagio' => Estagio::create(['nome' => 'Ovo', 'ordem' => 1, 'xp_marco' => 0])->id,
            'nome' => 'Smaug',
            'xp_ciclo' => 0,
            'status' => 'ATIVO',
            'data_nascimento' => '2026-11-01',
        ]);
    }

    public function test_crud_de_niveis_e_insignias(): void
    {
        $this->verificarCrud('/api/progression/levels', ['xp_necessario' => 100, 'xp_acumulado' => 100], ['xp_necessario' => 110]);
        $this->verificarCrud('/api/progression/badges', [
            'nome' => 'Primeiro passo',
            'descricao' => 'Concluiu o primeiro hábito',
            'icone_url' => 'passo.png',
        ], ['descricao' => 'Concluiu o primeiro hábito do dia']);
    }

    public function test_registra_xp_diario_para_o_usuario_autenticado(): void
    {
        $dragao = $this->dragaoDe($this->usuario);

        $this->postJson('/api/progression/daily-xp', [
            'fk_id_dragao' => $dragao->id,
            'data' => '2026-11-04',
            'percentual_ponderado' => 87.5,
            'xp_ganho' => 22,
        ])
            ->assertCreated()
            ->assertJsonPath('data.data', '2026-11-04')
            ->assertJsonPath('data.percentual_ponderado', '87.50')
            ->assertJsonPath('data.xp_ganho', 22);

        $this->assertDatabaseHas('xp_diario', ['fk_id_usuario' => $this->usuario->id, 'xp_ganho' => 22]);
    }

    public function test_nao_registra_xp_em_dragao_de_outro_usuario(): void
    {
        $alheio = $this->dragaoDe(User::factory()->create());

        $this->postJson('/api/progression/daily-xp', [
            'fk_id_dragao' => $alheio->id,
            'data' => '2026-11-04',
            'percentual_ponderado' => 50,
            'xp_ganho' => 12,
        ])->assertUnprocessable()->assertJsonValidationErrors(['fk_id_dragao']);
    }

    public function test_xp_diario_de_outro_usuario_fica_invisivel(): void
    {
        $outro = User::factory()->create();
        $xp = XpDiario::create([
            'fk_id_usuario' => $outro->id,
            'fk_id_dragao' => $this->dragaoDe($outro)->id,
            'data' => '2026-11-04',
            'percentual_ponderado' => 50,
            'xp_ganho' => 12,
        ]);

        $this->getJson('/api/progression/daily-xp')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/progression/daily-xp/{$xp->id}")->assertNotFound();
        $this->deleteJson("/api/progression/daily-xp/{$xp->id}")->assertNotFound();
    }
}
