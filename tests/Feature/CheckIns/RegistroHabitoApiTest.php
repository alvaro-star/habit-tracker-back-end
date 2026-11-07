<?php

namespace Tests\Feature\CheckIns;

use App\Models\Categoria;
use App\Models\Dragao;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Models\XpDiario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Cenário: hoje é 2026-11-09; o dragão ativo nasceu em 2026-11-01 e os
 * hábitos foram criados em 2026-11-02, salvo quando o teste diz outra coisa.
 */
class RegistroHabitoApiTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Dragao $dragao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-02 09:00:00');
        $this->usuario = User::factory()->create();
        $this->dragao = $this->dragaoAtivoDe($this->usuario);
        $this->travelTo('2026-11-09 10:00:00');
    }

    private function dragaoAtivoDe(User $usuario, string $nascimento = '2026-11-01'): Dragao
    {
        $dragao = Dragao::create([
            'fk_id_usuario' => $usuario->id,
            'nome' => 'Smaug',
            'xp_ciclo' => 0,
            'status' => 'ATIVO',
            'data_nascimento' => $nascimento,
        ]);
        $usuario->forceFill(['fk_id_dragao' => $dragao->id])->save();

        return $dragao;
    }

    private function habitoDe(
        User $usuario,
        int $peso = 10,
        string $criadoEm = '2026-11-02 09:00:00',
        string $tipoMeta = 'REALIZACAO',
        ?float $valorMeta = null,
    ): Habito {
        $agora = now();
        $this->travelTo($criadoEm);
        $habito = Habito::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_categoria' => Categoria::create(['nome' => 'Geral'.uniqid()])->id,
            'nome' => 'Ler',
            'peso' => $peso,
            'tipo_meta' => $tipoMeta,
            'valor_meta' => $valorMeta,
        ]);
        $this->travelTo($agora);

        return $habito;
    }

    /** @param array<string, mixed> $extra */
    private function marcar(Habito $habito, string $data, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/check-ins', [
            'fk_id_habito' => $habito->id,
            'data' => $data,
            'checked' => true,
            'status' => 'CONCLUIDO',
            ...$extra,
        ]);
    }

    private function desmarcar(Habito $habito, string $data): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/check-ins', ['fk_id_habito' => $habito->id, 'data' => $data, 'checked' => false]);
    }

    public function test_rotas_exigem_autenticacao(): void
    {
        $this->getJson('/api/check-ins')->assertUnauthorized();
        $this->postJson('/api/check-ins', [])->assertUnauthorized();
    }

    public function test_marca_check_in_de_hoje_e_grava_o_xp_de_hoje(): void
    {
        $habito = $this->habitoDe($this->usuario);
        Sanctum::actingAs($this->usuario);

        $this->marcar($habito, '2026-11-09')
            ->assertCreated()
            ->assertJsonPath('data.fk_id_habito', $habito->id)
            ->assertJsonPath('data.data', '2026-11-09')
            ->assertJsonPath('data.percentual', '100.00');

        // O XP de hoje também fica gravado e é creditado na hora.
        $this->assertDatabaseHas('xp_diario', ['data' => '2026-11-09', 'xp_ganho' => 25, 'fk_id_dragao' => $this->dragao->id]);
        $this->assertSame(25, $this->usuario->refresh()->xp_vitalicio);
        $this->assertSame(25, $this->dragao->refresh()->xp_ciclo);

        $this->desmarcar($habito, '2026-11-09')->assertNoContent();
        $this->assertDatabaseHas('xp_diario', ['data' => '2026-11-09', 'xp_ganho' => 0]);
        $this->assertSame(0, $this->usuario->refresh()->xp_vitalicio);
    }

    public function test_realizacao_usa_o_status_enviado(): void
    {
        $habito = $this->habitoDe($this->usuario);
        Sanctum::actingAs($this->usuario);

        $this->marcar($habito, '2026-11-09', ['status' => 'EM_ANDAMENTO', 'percentual' => 30])
            ->assertJsonPath('data.status', 'EM_ANDAMENTO')
            ->assertJsonPath('data.percentual', '30.00');

        $this->marcar($habito, '2026-11-09', ['status' => 'NAO_CONCLUIDO'])
            ->assertJsonPath('data.status', 'NAO_CONCLUIDO')
            ->assertJsonPath('data.percentual', '0.00');

        // Sem status, um hábito de realização fica como não concluído.
        $this->marcar($habito, '2026-11-09', ['status' => null])->assertJsonPath('data.status', 'NAO_CONCLUIDO');
    }

    /** @return array<string, array{0: string, 1: float|null, 2: float|null, 3: string, 4: string}> */
    public static function cumprimentoPorValor(): array
    {
        return [
            'tempo: meta atingida' => ['TEMPO', 30, 30, 'CONCLUIDO', '100.00'],
            'tempo: passou da meta' => ['TEMPO', 30, 45, 'CONCLUIDO', '100.00'],
            'tempo: parcial' => ['TEMPO', 30, 12, 'EM_ANDAMENTO', '40.00'],
            'tempo: sem valor' => ['TEMPO', 30, null, 'NAO_CONCLUIDO', '0.00'],
            'tempo: zero' => ['TEMPO', 30, 0, 'NAO_CONCLUIDO', '0.00'],
            'quantidade: completa' => ['QUANTIDADE', 8, 8, 'CONCLUIDO', '100.00'],
            'quantidade: parcial' => ['QUANTIDADE', 8, 2, 'EM_ANDAMENTO', '25.00'],
            'quantidade: sem valor' => ['QUANTIDADE', 8, null, 'NAO_CONCLUIDO', '0.00'],
            'quantidade sem meta: qualquer valor conclui' => ['QUANTIDADE', null, 3, 'CONCLUIDO', '100.00'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cumprimentoPorValor')]
    public function test_tempo_e_quantidade_calculam_o_status_pelo_valor(string $tipo, ?float $meta, ?float $valor, string $status, string $percentual): void
    {
        $habito = $this->habitoDe($this->usuario, tipoMeta: $tipo, valorMeta: $meta);
        Sanctum::actingAs($this->usuario);

        // O status enviado é ignorado em tempo e quantidade.
        $this->marcar($habito, '2026-11-09', ['status' => 'CONCLUIDO', 'valor_realizado' => $valor])
            ->assertJsonPath('data.status', $status)
            ->assertJsonPath('data.percentual', $percentual);
    }

    public function test_tempo_parcial_rende_xp_proporcional(): void
    {
        $habito = $this->habitoDe($this->usuario, tipoMeta: 'TEMPO', valorMeta: 60);
        Sanctum::actingAs($this->usuario);

        // 30 de 60 minutos: 50% do peso → 12,5 → 13 XP.
        $this->marcar($habito, '2026-11-05', ['valor_realizado' => 30])->assertCreated();
        $this->assertDatabaseHas('xp_diario', ['data' => '2026-11-05', 'xp_ganho' => 13]);

        // Completar os 60 minutos sobe para 25 XP.
        $this->marcar($habito, '2026-11-05', ['valor_realizado' => 60])->assertOk()->assertJsonPath('data.status', 'CONCLUIDO');
        $this->assertSame(25, $this->usuario->refresh()->xp_vitalicio);
    }

    public function test_marcar_de_novo_atualiza_o_check_in_do_mesmo_dia(): void
    {
        $habito = $this->habitoDe($this->usuario);
        Sanctum::actingAs($this->usuario);

        $id = $this->marcar($habito, '2026-11-09', ['status' => 'EM_ANDAMENTO', 'percentual' => 40])->assertCreated()->json('data.id');

        $this->marcar($habito, '2026-11-09')
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.status', 'CONCLUIDO')
            ->assertJsonPath('data.percentual', '100.00'); // concluído grava 100%

        $this->assertDatabaseCount('registro_habito', 1);
    }

    public function test_marcar_dia_passado_recalcula_o_xp_e_soma_no_dragao_e_no_usuario(): void
    {
        $agua = $this->habitoDe($this->usuario, peso: 5);
        $estudar = $this->habitoDe($this->usuario, peso: 15);
        Sanctum::actingAs($this->usuario);

        // 5 de 20 pontos de peso: 5/20 × 25 = 6,25 → 6 XP.
        $this->marcar($agua, '2026-11-05')->assertCreated();
        $this->assertDatabaseHas('xp_diario', ['fk_id_usuario' => $this->usuario->id, 'data' => '2026-11-05', 'xp_ganho' => 6, 'fk_id_dragao' => $this->dragao->id]);
        $this->assertSame(6, $this->usuario->refresh()->xp_vitalicio);
        $this->assertSame(6, $this->dragao->refresh()->xp_ciclo);

        // Completa o dia: 25 XP; soma só a diferença (19).
        $this->marcar($estudar, '2026-11-05')->assertCreated();
        $this->assertDatabaseHas('xp_diario', ['data' => '2026-11-05', 'xp_ganho' => 25]);
        $this->assertDatabaseCount('xp_diario', 1);
        $this->assertSame(25, $this->usuario->refresh()->xp_vitalicio);
        $this->assertSame(25, $this->dragao->refresh()->xp_ciclo);

        // Marcar de novo o mesmo hábito não credita XP em dobro.
        $this->marcar($estudar, '2026-11-05')->assertOk();
        $this->assertSame(25, $this->usuario->refresh()->xp_vitalicio);
    }

    public function test_xp_do_check_in_atualiza_o_estagio_do_dragao(): void
    {
        $this->dragao->update(['xp_ciclo' => 110]);
        $habito = $this->habitoDe($this->usuario);
        Sanctum::actingAs($this->usuario);

        // 110 + 25 = 135 XP no ciclo: passa do marco de 120 (filhote).
        $this->marcar($habito, '2026-11-05')->assertCreated();
        $this->assertSame(135, $this->dragao->refresh()->xp_ciclo);
        $this->assertSame(\App\Enums\EstagioDragao::FILHOTE, $this->dragao->estagio);

        // Desmarcar volta a 110 XP e ao ovo.
        $this->desmarcar($habito, '2026-11-05')->assertNoContent();
        $this->assertSame(\App\Enums\EstagioDragao::OVO, $this->dragao->refresh()->estagio);
    }

    public function test_desmarcar_exclui_o_check_in_e_desconta_o_xp(): void
    {
        $habito = $this->habitoDe($this->usuario);
        Sanctum::actingAs($this->usuario);

        $this->marcar($habito, '2026-11-05')->assertCreated();
        $this->assertSame(25, $this->usuario->refresh()->xp_vitalicio);

        $this->desmarcar($habito, '2026-11-05')->assertNoContent();

        $this->assertDatabaseCount('registro_habito', 0);
        $this->assertDatabaseHas('xp_diario', ['data' => '2026-11-05', 'xp_ganho' => 0]);
        $this->assertSame(0, $this->usuario->refresh()->xp_vitalicio);
        $this->assertSame(0, $this->dragao->refresh()->xp_ciclo);

        // Desmarcar o que não está marcado não falha.
        $this->desmarcar($habito, '2026-11-05')->assertNoContent();
    }

    public function test_recusa_data_futura(): void
    {
        $habito = $this->habitoDe($this->usuario);
        Sanctum::actingAs($this->usuario);

        $this->marcar($habito, '2026-11-10')->assertUnprocessable()->assertJsonValidationErrors(['data']);
        $this->desmarcar($habito, '2026-11-10')->assertUnprocessable()->assertJsonValidationErrors(['data']);
        $this->assertDatabaseCount('registro_habito', 0);
    }

    public function test_recusa_data_anterior_ao_nascimento_do_dragao(): void
    {
        $habito = $this->habitoDe($this->usuario, criadoEm: '2026-10-01 09:00:00');
        Sanctum::actingAs($this->usuario);

        $this->marcar($habito, '2026-10-31')->assertUnprocessable()->assertJsonValidationErrors(['data']);
        $this->assertDatabaseCount('registro_habito', 0);
    }

    public function test_data_anterior_a_criacao_do_habito_e_conflito(): void
    {
        $habito = $this->habitoDe($this->usuario, criadoEm: '2026-11-06 15:00:00');
        Sanctum::actingAs($this->usuario);

        $this->marcar($habito, '2026-11-05')->assertStatus(409)->assertJsonValidationErrors(['data']);
        $this->desmarcar($habito, '2026-11-05')->assertStatus(409);

        // No próprio dia da criação, pode.
        $this->marcar($habito, '2026-11-06')->assertCreated();
    }

    public function test_criar_habito_reduz_o_xp_de_hoje_e_desativar_devolve(): void
    {
        $ler = $this->habitoDe($this->usuario, peso: 10);
        Sanctum::actingAs($this->usuario);
        $this->marcar($ler, '2026-11-09')->assertCreated();
        $this->assertSame(25, $this->usuario->refresh()->xp_vitalicio);

        // Um hábito novo de peso 10 entra no total de hoje: 10/20 × 25 = 12,5 → 13 XP.
        $novo = $this->postJson('/api/habits', [
            'fk_id_categoria' => Categoria::create(['nome' => 'Saúde'])->id,
            'nome' => 'Correr',
            'polaridade' => 'BOM',
            'tipo_meta' => 'REALIZACAO',
            'frequencia' => 'DIARIA',
            'dia_frequencia' => null,
            'peso' => 10,
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('xp_diario', ['data' => '2026-11-09', 'xp_ganho' => 13]);
        $this->assertSame(13, $this->usuario->refresh()->xp_vitalicio);
        $this->assertSame(13, $this->dragao->refresh()->xp_ciclo);

        // Desativar o hábito novo o tira do total: volta a 25 XP.
        $this->deleteJson("/api/habits/{$novo}")->assertNoContent();
        $this->assertDatabaseHas('xp_diario', ['data' => '2026-11-09', 'xp_ganho' => 25]);
        $this->assertSame(25, $this->usuario->refresh()->xp_vitalicio);
        $this->assertSame(25, $this->dragao->refresh()->xp_ciclo);
    }

    public function test_criar_habito_sem_dragao_ativo_nao_grava_xp(): void
    {
        $semDragao = User::factory()->create();
        Sanctum::actingAs($semDragao);

        $this->postJson('/api/habits', [
            'fk_id_categoria' => Categoria::create(['nome' => 'Saúde'])->id,
            'nome' => 'Correr',
            'polaridade' => 'BOM',
            'tipo_meta' => 'REALIZACAO',
            'frequencia' => 'DIARIA',
            'dia_frequencia' => null,
            'peso' => 10,
        ])->assertCreated();

        $this->assertDatabaseCount('xp_diario', 0);
    }

    public function test_usuario_sem_dragao_ativo_nao_registra_check_in(): void
    {
        $semDragao = User::factory()->create();
        $habito = $this->habitoDe($semDragao);
        Sanctum::actingAs($semDragao);

        $this->marcar($habito, '2026-11-09')->assertUnprocessable()->assertJsonValidationErrors(['fk_id_habito']);
    }

    public function test_nao_registra_check_in_em_habito_de_outro_usuario(): void
    {
        $habitoAlheio = $this->habitoDe(User::factory()->create());
        Sanctum::actingAs($this->usuario);

        $this->marcar($habitoAlheio, '2026-11-09')->assertUnprocessable()->assertJsonValidationErrors(['fk_id_habito']);
    }

    public function test_valida_checked_status_data_e_percentual(): void
    {
        $habito = $this->habitoDe($this->usuario);
        Sanctum::actingAs($this->usuario);

        $this->postJson('/api/check-ins', ['fk_id_habito' => $habito->id, 'data' => '2026-11-09'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['checked']);

        $this->postJson('/api/check-ins', [
            'fk_id_habito' => $habito->id,
            'data' => '09/11/2026',
            'checked' => true,
            'status' => 'ESTRANHO',
            'percentual' => 150,
        ])->assertUnprocessable()->assertJsonValidationErrors(['data', 'status', 'percentual']);

        // Ao desmarcar, o status não é exigido.
        $this->desmarcar($habito, '2026-11-09')->assertNoContent();
    }

    public function test_lista_e_mostra_apenas_check_ins_do_usuario(): void
    {
        $meu = RegistroHabito::create(['fk_id_habito' => $this->habitoDe($this->usuario)->id, 'data' => '2026-11-04', 'status' => 'CONCLUIDO']);
        $alheio = RegistroHabito::create(['fk_id_habito' => $this->habitoDe(User::factory()->create())->id, 'data' => '2026-11-04', 'status' => 'CONCLUIDO']);
        Sanctum::actingAs($this->usuario);

        $this->getJson('/api/check-ins')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $meu->id);
        $this->getJson("/api/check-ins/{$meu->id}")->assertOk();
        $this->getJson("/api/check-ins/{$alheio->id}")->assertNotFound();
    }

    public function test_check_in_nao_tem_put_nem_delete(): void
    {
        $registro = RegistroHabito::create(['fk_id_habito' => $this->habitoDe($this->usuario)->id, 'data' => '2026-11-04', 'status' => 'EM_ANDAMENTO']);
        Sanctum::actingAs($this->usuario);

        $this->putJson("/api/check-ins/{$registro->id}", ['status' => 'CONCLUIDO'])->assertMethodNotAllowed();
        $this->patchJson("/api/check-ins/{$registro->id}", ['status' => 'CONCLUIDO'])->assertMethodNotAllowed();
        $this->deleteJson("/api/check-ins/{$registro->id}")->assertMethodNotAllowed();

        $this->assertDatabaseHas('registro_habito', ['id' => $registro->id, 'status' => 'EM_ANDAMENTO']);
    }

    public function test_indice_de_habito_e_data_existe(): void
    {
        $this->assertTrue(Schema::hasIndex('registro_habito', ['fk_id_habito', 'data']));
    }
}
