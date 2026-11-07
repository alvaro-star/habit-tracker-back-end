<?php

namespace Tests\Feature\Habits;

use App\Models\Categoria;
use App\Models\Habito;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HabitoApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_merge([
            'fk_id_categoria' => Categoria::create(['nome' => 'Saude'.uniqid()])->id,
            'nome' => 'Beber agua',
            'polaridade' => 'BOM',
            'tipo_meta' => 'REALIZACAO',
            'frequencia' => 'DIARIA',
            'dia_frequencia' => null,
            'dificuldade' => 'FACIL',
        ], $extra);
    }

    public function test_rotas_exigem_autenticacao(): void
    {
        $this->getJson('/api/habits')->assertUnauthorized();
        $this->postJson('/api/habits', $this->payload())->assertUnauthorized();
    }

    public function test_cria_habito_do_usuario_autenticado_com_peso_da_dificuldade(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $this->postJson('/api/habits', $this->payload(['dificuldade' => 'DIFICIL']))
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Beber agua')
            ->assertJsonPath('data.peso', 15);

        $this->assertDatabaseHas('habito', ['fk_id_usuario' => $usuario->id, 'nome' => 'Beber agua', 'peso' => 15]);
    }

    public function test_valida_campos_obrigatorios_e_enums(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/habits', ['polaridade' => 'INVALIDA'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fk_id_categoria', 'nome', 'polaridade', 'tipo_meta', 'frequencia', 'dificuldade']);
    }

    public function test_lista_apenas_habitos_do_usuario(): void
    {
        $eu = User::factory()->create();
        $outro = User::factory()->create();
        $categoria = Categoria::create(['nome' => 'Geral']);
        $meu = Habito::create(['fk_id_usuario' => $eu->id, 'fk_id_categoria' => $categoria->id, 'nome' => 'Meu']);
        Habito::create(['fk_id_usuario' => $outro->id, 'fk_id_categoria' => $categoria->id, 'nome' => 'Alheio']);

        Sanctum::actingAs($eu);

        $this->getJson('/api/habits')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $meu->id);
    }

    public function test_nao_acessa_habito_de_outro_usuario(): void
    {
        $outro = User::factory()->create();
        $habito = Habito::create([
            'fk_id_usuario' => $outro->id,
            'fk_id_categoria' => Categoria::create(['nome' => 'Geral'])->id,
            'nome' => 'Alheio',
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/habits/{$habito->id}")->assertNotFound();
        $this->putJson("/api/habits/{$habito->id}", ['nome' => 'Invadido'])->assertNotFound();
        $this->deleteJson("/api/habits/{$habito->id}")->assertNotFound();

        $this->assertDatabaseHas('habito', ['id' => $habito->id, 'nome' => 'Alheio']);
    }

    public function test_atualiza_habito_recalculando_peso_pela_dificuldade(): void
    {
        $usuario = User::factory()->create();
        $habito = Habito::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_categoria' => Categoria::create(['nome' => 'Geral'])->id,
            'nome' => 'Ler',
        ]);
        Sanctum::actingAs($usuario);

        $this->putJson("/api/habits/{$habito->id}", ['dificuldade' => 'MUITO_DIFICIL'])
            ->assertOk()
            ->assertJsonPath('data.dificuldade', 'MUITO_DIFICIL')
            ->assertJsonPath('data.peso', 20);
    }

    public function test_post_ignora_peso_e_created_at_informados(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $resposta = $this->postJson('/api/habits', $this->payload([
            'dificuldade' => 'MEDIO',
            'peso' => 1,
            'created_at' => '2020-01-01 00:00:00',
        ]))->assertCreated()->assertJsonPath('data.peso', 10);

        $this->assertNotNull($resposta->json('data.created_at'));
        $this->assertStringStartsNotWith('2020-01-01', $resposta->json('data.created_at'));
    }

    public function test_put_nao_altera_peso_nem_created_at(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);
        $habito = $this->postJson('/api/habits', $this->payload(['dificuldade' => 'DIFICIL']))->json('data');

        $this->putJson("/api/habits/{$habito['id']}", ['peso' => 1, 'created_at' => '2020-01-01 00:00:00'])
            ->assertOk()
            ->assertJsonPath('data.peso', 15)
            ->assertJsonPath('data.created_at', $habito['created_at']);
    }

    public function test_cria_habitos_semanal_e_mensal_com_dia_frequencia(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/habits', $this->payload(['frequencia' => 'SEMANAL', 'dia_frequencia' => 'SEG']))
            ->assertCreated()
            ->assertJsonPath('data.dia_frequencia', 'SEG');

        $this->postJson('/api/habits', $this->payload(['frequencia' => 'MENSAL', 'dia_frequencia' => 15]))
            ->assertCreated()
            ->assertJsonPath('data.dia_frequencia', 15);

        $this->postJson('/api/habits', $this->payload(['frequencia' => 'DIARIA']))
            ->assertCreated()
            ->assertJsonPath('data.dia_frequencia', null);
    }

    /** @return array<string, array{0: string, 1: mixed}> */
    public static function diasFrequenciaInvalidos(): array
    {
        return [
            'diário com dia' => ['DIARIA', 'SEG'],
            'semanal sem dia' => ['SEMANAL', null],
            'semanal com número' => ['SEMANAL', 15],
            'semanal com dia inexistente' => ['SEMANAL', 'XYZ'],
            'mensal sem dia' => ['MENSAL', null],
            'mensal com dia da semana' => ['MENSAL', 'SEG'],
            'mensal com dia 0' => ['MENSAL', 0],
            'mensal com dia 32' => ['MENSAL', 32],
        ];
    }

    public function test_frequencia_e_dia_frequencia_devem_ser_enviados_juntos(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $semDia = $this->payload();
        unset($semDia['dia_frequencia']);
        $this->postJson('/api/habits', $semDia)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dia_frequencia']);

        $id = $this->postJson('/api/habits', $this->payload())->assertCreated()->json('data.id');

        $this->patchJson("/api/habits/{$id}", ['frequencia' => 'DIARIA'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dia_frequencia']);

        $this->patchJson("/api/habits/{$id}", ['dia_frequencia' => 'SEG'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['frequencia']);

        $this->patchJson("/api/habits/{$id}", ['dia_frequencia' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['frequencia']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('diasFrequenciaInvalidos')]
    public function test_post_recusa_dia_frequencia_incompativel(string $frequencia, mixed $dia): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/habits', $this->payload(['frequencia' => $frequencia, 'dia_frequencia' => $dia]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dia_frequencia']);

        $this->assertDatabaseCount('habito', 0);
    }

    public function test_put_atualiza_o_dia_enviando_a_frequencia_junto(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $id = $this->postJson('/api/habits', $this->payload(['frequencia' => 'MENSAL', 'dia_frequencia' => 10]))->json('data.id');

        $this->patchJson("/api/habits/{$id}", ['frequencia' => 'MENSAL', 'dia_frequencia' => 'SEG'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dia_frequencia']);

        $this->patchJson("/api/habits/{$id}", ['frequencia' => 'MENSAL', 'dia_frequencia' => 20])
            ->assertOk()
            ->assertJsonPath('data.dia_frequencia', 20);

        $this->assertDatabaseHas('habito', ['id' => $id, 'dia_frequencia' => '20']);
    }

    public function test_put_troca_frequencia_exigindo_dia_e_limpa_o_dia_ao_virar_diario(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $id = $this->postJson('/api/habits', $this->payload(['frequencia' => 'SEMANAL', 'dia_frequencia' => 'QUA']))->json('data.id');

        $this->patchJson("/api/habits/{$id}", ['frequencia' => 'MENSAL'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dia_frequencia']);

        $this->patchJson("/api/habits/{$id}", ['frequencia' => 'DIARIA', 'dia_frequencia' => null])
            ->assertOk()
            ->assertJsonPath('data.frequencia', 'DIARIA')
            ->assertJsonPath('data.dia_frequencia', null);
    }

    public function test_service_recusa_frequencia_invalida_com_excecao_de_regra_de_negocio(): void
    {
        $this->expectException(\App\Exceptions\RegraDeNegocioException::class);

        app(\App\Services\HabitoService::class)->validarFrequencia(\App\Enums\Frequencia::SEMANAL, 31);
    }

    public function test_lista_habitos_que_devem_ser_cumpridos_no_dia(): void
    {
        $eu = User::factory()->create();
        $outro = User::factory()->create();
        $categoria = Categoria::create(['nome' => 'Geral']);
        $criar = fn (User $dono, string $nome, string $frequencia, ?string $dia = null, bool $ativo = true) => Habito::create([
            'fk_id_usuario' => $dono->id,
            'fk_id_categoria' => $categoria->id,
            'nome' => $nome,
            'frequencia' => $frequencia,
            'dia_frequencia' => $dia,
            'ativo' => $ativo,
        ]);

        $this->travelTo('2026-01-01');
        $diario = $criar($eu, 'Diário', 'DIARIA');
        $segunda = $criar($eu, 'Segunda', 'SEMANAL', 'SEG');
        $criar($eu, 'Terça', 'SEMANAL', 'TER');
        $dia9 = $criar($eu, 'Dia 9', 'MENSAL', '9');
        $criar($eu, 'Dia 10', 'MENSAL', '10');
        $criar($eu, 'Inativo', 'DIARIA', null, false);
        $criar($outro, 'De outro usuário', 'DIARIA');
        $this->travelTo('2026-11-20');
        $criar($eu, 'Criado depois', 'DIARIA');
        $this->travelBack();

        Sanctum::actingAs($eu);

        // 2026-11-09 é uma segunda-feira.
        $this->getJson('/api/habits/daily?data=2026-11-09')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.*.id', [$diario->id, $segunda->id, $dia9->id]);
    }

    public function test_habito_mensal_do_dia_31_so_aparece_nos_meses_que_tem_esse_dia(): void
    {
        $usuario = User::factory()->create();
        $this->travelTo('2026-01-01');
        $dia31 = Habito::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_categoria' => Categoria::create(['nome' => 'Geral'])->id,
            'nome' => 'Fechar o mês',
            'frequencia' => 'MENSAL',
            'dia_frequencia' => '31',
        ]);
        $this->travelBack();

        Sanctum::actingAs($usuario);

        $this->getJson('/api/habits/daily?data=2026-04-30')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/habits/daily?data=2026-05-31')->assertOk()->assertJsonPath('data.0.id', $dia31->id);
    }

    /** @return array<string, array{0: string}> */
    public static function datasInvalidas(): array
    {
        return [
            'dia que abril não tem' => ['2026-04-31'],
            '29 de fevereiro fora de ano bissexto' => ['2026-02-29'],
            'mês 13' => ['2026-13-01'],
            'formato brasileiro' => ['09/11/2026'],
            'texto' => ['amanha'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('datasInvalidas')]
    public function test_habitos_do_dia_recusa_data_inexistente(string $data): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/habits/daily?data={$data}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['data']);
    }

    public function test_habitos_do_dia_exige_autenticacao_e_aceita_data_valida(): void
    {
        $this->getJson('/api/habits/daily')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/habits/daily')->assertOk();
        $this->getJson('/api/habits/daily?data=2028-02-29')->assertOk();
    }

    public function test_exclui_habito_do_usuario(): void
    {
        $usuario = User::factory()->create();
        $habito = Habito::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_categoria' => Categoria::create(['nome' => 'Geral'])->id,
            'nome' => 'Ler',
        ]);
        Sanctum::actingAs($usuario);

        $this->deleteJson("/api/habits/{$habito->id}")->assertNoContent();
        $this->assertDatabaseMissing('habito', ['id' => $habito->id]);
    }
}
