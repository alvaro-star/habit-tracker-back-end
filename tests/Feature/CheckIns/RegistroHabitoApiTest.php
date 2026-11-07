<?php

namespace Tests\Feature\CheckIns;

use App\Models\Categoria;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegistroHabitoApiTest extends TestCase
{
    use RefreshDatabase;

    private function habitoDe(User $usuario): Habito
    {
        return Habito::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_categoria' => Categoria::create(['nome' => 'Geral'.uniqid()])->id,
            'nome' => 'Ler',
        ]);
    }

    public function test_rotas_exigem_autenticacao(): void
    {
        $this->getJson('/api/check-ins')->assertUnauthorized();
    }

    public function test_registra_check_in_em_habito_do_usuario(): void
    {
        $usuario = User::factory()->create();
        $habito = $this->habitoDe($usuario);
        Sanctum::actingAs($usuario);

        $this->postJson('/api/check-ins', [
            'fk_id_habito' => $habito->id,
            'data' => '2026-11-04',
            'status' => 'CONCLUIDO',
        ])
            ->assertCreated()
            ->assertJsonPath('data.fk_id_habito', $habito->id)
            ->assertJsonPath('data.data', '2026-11-04')
            ->assertJsonPath('data.percentual', '100.00');
    }

    public function test_nao_registra_check_in_em_habito_de_outro_usuario(): void
    {
        $habitoAlheio = $this->habitoDe(User::factory()->create());
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/check-ins', [
            'fk_id_habito' => $habitoAlheio->id,
            'data' => '2026-11-04',
            'status' => 'CONCLUIDO',
        ])->assertUnprocessable()->assertJsonValidationErrors(['fk_id_habito']);
    }

    public function test_valida_status_data_e_percentual(): void
    {
        $usuario = User::factory()->create();
        $habito = $this->habitoDe($usuario);
        Sanctum::actingAs($usuario);

        $this->postJson('/api/check-ins', [
            'fk_id_habito' => $habito->id,
            'data' => '04/11/2026',
            'status' => 'ESTRANHO',
            'percentual' => 150,
        ])->assertUnprocessable()->assertJsonValidationErrors(['data', 'status', 'percentual']);
    }

    public function test_lista_apenas_check_ins_do_usuario(): void
    {
        $eu = User::factory()->create();
        $outro = User::factory()->create();
        $meuHabito = $this->habitoDe($eu);
        $meu = RegistroHabito::create(['fk_id_habito' => $meuHabito->id, 'data' => '2026-11-04', 'status' => 'CONCLUIDO']);
        RegistroHabito::create(['fk_id_habito' => $this->habitoDe($outro)->id, 'data' => '2026-11-04', 'status' => 'CONCLUIDO']);

        Sanctum::actingAs($eu);

        $this->getJson('/api/check-ins')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $meu->id);
    }

    public function test_nao_acessa_check_in_de_outro_usuario(): void
    {
        $outro = User::factory()->create();
        $registro = RegistroHabito::create([
            'fk_id_habito' => $this->habitoDe($outro)->id,
            'data' => '2026-11-04',
            'status' => 'CONCLUIDO',
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/check-ins/{$registro->id}")->assertNotFound();
        $this->putJson("/api/check-ins/{$registro->id}", ['status' => 'NAO_CONCLUIDO'])->assertNotFound();
        $this->deleteJson("/api/check-ins/{$registro->id}")->assertNotFound();

        $this->assertDatabaseHas('registro_habito', ['id' => $registro->id, 'status' => 'CONCLUIDO']);
    }

    public function test_atualiza_status_e_nao_troca_o_habito(): void
    {
        $usuario = User::factory()->create();
        $habito = $this->habitoDe($usuario);
        $outroHabito = $this->habitoDe($usuario);
        $registro = RegistroHabito::create(['fk_id_habito' => $habito->id, 'data' => '2026-11-04', 'status' => 'EM_ANDAMENTO']);
        Sanctum::actingAs($usuario);

        $this->putJson("/api/check-ins/{$registro->id}", [
            'status' => 'CONCLUIDO',
            'fk_id_habito' => $outroHabito->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'CONCLUIDO')
            ->assertJsonPath('data.fk_id_habito', $habito->id);
    }

    public function test_exclui_check_in_do_usuario(): void
    {
        $usuario = User::factory()->create();
        $registro = RegistroHabito::create([
            'fk_id_habito' => $this->habitoDe($usuario)->id,
            'data' => '2026-11-04',
            'status' => 'CONCLUIDO',
        ]);
        Sanctum::actingAs($usuario);

        $this->deleteJson("/api/check-ins/{$registro->id}")->assertNoContent();
        $this->assertDatabaseMissing('registro_habito', ['id' => $registro->id]);
    }
}
