<?php

namespace Tests\Feature\Habits;

use App\Enums\Frequencia;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Categoria;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Services\HabitoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
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
            'peso' => 5,
        ], $extra);
    }

    public function test_rotas_exigem_autenticacao(): void
    {
        $this->getJson('/api/habits')->assertUnauthorized();
        $this->postJson('/api/habits', $this->payload())->assertUnauthorized();
    }

    public function test_cria_habito_do_usuario_autenticado_com_dificuldade_do_peso(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $this->postJson('/api/habits', $this->payload(['peso' => 12]))
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Beber agua')
            ->assertJsonPath('data.peso', 12)
            ->assertJsonPath('data.dificuldade', 'DIFICIL');

        $this->assertDatabaseHas('habito', ['fk_id_usuario' => $usuario->id, 'peso' => 12, 'dificuldade' => 'DIFICIL']);
    }

    /** @return array<string, array{0: int, 1: string}> */
    public static function pesosEDificuldades(): array
    {
        return [
            'peso 1' => [1, 'FACIL'],
            'peso 5' => [5, 'FACIL'],
            'peso 6' => [6, 'MEDIO'],
            'peso 10' => [10, 'MEDIO'],
            'peso 11' => [11, 'DIFICIL'],
            'peso 15' => [15, 'DIFICIL'],
            'peso 16' => [16, 'MUITO_DIFICIL'],
            'peso 20' => [20, 'MUITO_DIFICIL'],
        ];
    }

    #[DataProvider('pesosEDificuldades')]
    public function test_o_peso_define_a_dificuldade(int $peso, string $dificuldade): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/habits', $this->payload(['peso' => $peso]))
            ->assertCreated()
            ->assertJsonPath('data.dificuldade', $dificuldade);
    }

    /** @return array<string, array{0: mixed}> */
    public static function pesosInvalidos(): array
    {
        return [
            'acima de 20' => [21],
            'zero' => [0],
            'negativo' => [-5],
            'decimal' => [7.5],
            'texto' => ['pesado'],
        ];
    }

    #[DataProvider('pesosInvalidos')]
    public function test_recusa_peso_fora_de_1_a_20(mixed $peso): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/habits', $this->payload(['peso' => $peso]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['peso']);
    }

    public function test_valida_campos_obrigatorios_e_enums(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/habits', ['polaridade' => 'INVALIDA'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fk_id_categoria', 'nome', 'polaridade', 'tipo_meta', 'frequencia', 'peso']);
    }

    public function test_service_recusa_peso_acima_de_20_sem_passar_pela_request(): void
    {
        $this->expectException(RegraDeNegocioException::class);

        app(HabitoService::class)->criar([
            'fk_id_usuario' => User::factory()->create()->id,
            'fk_id_categoria' => Categoria::create(['nome' => 'Geral'])->id,
            'nome' => 'Ler',
            'peso' => 30,
        ]);
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

    public function test_atualiza_habito_recalculando_a_dificuldade_pelo_peso(): void
    {
        $usuario = User::factory()->create();
        $habito = Habito::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_categoria' => Categoria::create(['nome' => 'Geral'])->id,
            'nome' => 'Ler',
        ]);
        Sanctum::actingAs($usuario);

        $this->putJson("/api/habits/{$habito->id}", ['peso' => 18])
            ->assertOk()
            ->assertJsonPath('data.peso', 18)
            ->assertJsonPath('data.dificuldade', 'MUITO_DIFICIL');

        $this->putJson("/api/habits/{$habito->id}", ['peso' => 25])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['peso']);
    }

    public function test_post_ignora_a_dificuldade_informada(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/habits', $this->payload(['peso' => 8, 'dificuldade' => 'MUITO_DIFICIL']))
            ->assertCreated()
            ->assertJsonPath('data.dificuldade', 'MEDIO');
    }

    public function test_created_at_vem_do_timestamp_e_nao_pode_ser_enviado(): void
    {
        $this->travelTo('2026-11-09 08:30:00');
        Sanctum::actingAs(User::factory()->create());

        foreach (['created_at', 'data_criacao'] as $campo) {
            $this->postJson('/api/habits', $this->payload([$campo => '2020-01-01 00:00:00']))
                ->assertUnprocessable()
                ->assertJsonValidationErrors([$campo]);
        }
        $this->assertDatabaseCount('habito', 0);

        $resposta = $this->postJson('/api/habits', $this->payload())->assertCreated();
        $this->assertStringStartsWith('2026-11-09T08:30:00', $resposta->json('data.created_at'));

        $this->putJson('/api/habits/'.$resposta->json('data.id'), ['created_at' => '2020-01-01 00:00:00'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['created_at']);
    }

    public function test_put_nao_altera_dificuldade_nem_created_at(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);
        $habito = $this->postJson('/api/habits', $this->payload(['peso' => 15]))->json('data');

        $this->putJson("/api/habits/{$habito['id']}", ['dificuldade' => 'FACIL', 'nome' => 'Outro nome'])
            ->assertOk()
            ->assertJsonPath('data.peso', 15)
            ->assertJsonPath('data.dificuldade', 'DIFICIL')
            ->assertJsonPath('data.created_at', $habito['created_at']);
    }

    public function test_tipo_de_meta_so_muda_sem_check_ins(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $id = $this->postJson('/api/habits', $this->payload(['tipo_meta' => 'REALIZACAO']))->json('data.id');

        // Sem check-ins, pode mudar.
        $this->patchJson("/api/habits/{$id}", ['tipo_meta' => 'TEMPO'])
            ->assertOk()
            ->assertJsonPath('data.tipo_meta', 'TEMPO');

        RegistroHabito::create(['fk_id_habito' => $id, 'data' => '2026-11-07', 'status' => 'CONCLUIDO']);

        // Com check-in, mudar o tipo de meta devolve 422.
        $this->patchJson("/api/habits/{$id}", ['tipo_meta' => 'QUANTIDADE'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tipo_meta']);
        $this->assertDatabaseHas('habito', ['id' => $id, 'tipo_meta' => 'TEMPO']);

        // Reenviar o mesmo tipo de meta, ou editar outros campos, continua permitido.
        $this->patchJson("/api/habits/{$id}", ['tipo_meta' => 'TEMPO', 'nome' => 'Ler 30 minutos'])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Ler 30 minutos');
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

    #[DataProvider('diasFrequenciaInvalidos')]
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
        $this->expectException(RegraDeNegocioException::class);

        app(HabitoService::class)->validarFrequencia(Frequencia::SEMANAL, 31);
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

    #[DataProvider('datasInvalidas')]
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

    public function test_nao_exclui_habito_criado_antes_de_hoje(): void
    {
        $usuario = User::factory()->create();
        $this->travelTo('2026-11-08 20:00:00');
        $habito = Habito::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_categoria' => Categoria::create(['nome' => 'Geral'])->id,
            'nome' => 'Ler',
        ]);
        $this->travelTo('2026-11-09 08:00:00');
        Sanctum::actingAs($usuario);

        $this->deleteJson("/api/habits/{$habito->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);

        $this->assertDatabaseHas('habito', ['id' => $habito->id, 'ativo' => true, 'inactivated_at' => null]);
    }

    public function test_excluir_grava_inactivated_at_e_reativar_limpa(): void
    {
        $this->travelTo('2026-11-09 08:00:00');
        $usuario = User::factory()->create();
        $habito = Habito::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_categoria' => Categoria::create(['nome' => 'Geral'])->id,
            'nome' => 'Ler',
        ]);
        Sanctum::actingAs($usuario);

        $this->travelTo('2026-11-09 18:30:00');
        $this->deleteJson("/api/habits/{$habito->id}")->assertNoContent();
        $this->getJson("/api/habits/{$habito->id}")
            ->assertJsonPath('data.ativo', false)
            ->assertJsonPath('data.inactivated_at', '2026-11-09T18:30:00.000000Z');

        $this->patchJson("/api/habits/{$habito->id}", ['ativo' => true])
            ->assertOk()
            ->assertJsonPath('data.inactivated_at', null);

        // Desativar pelo PATCH também guarda a data.
        $this->patchJson("/api/habits/{$habito->id}", ['ativo' => false])
            ->assertOk()
            ->assertJsonPath('data.inactivated_at', '2026-11-09T18:30:00.000000Z');
    }

    public function test_excluir_desativa_o_habito_sem_apagar_o_registro(): void
    {
        $usuario = User::factory()->create();
        $habito = Habito::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_categoria' => Categoria::create(['nome' => 'Geral'])->id,
            'nome' => 'Ler',
        ]);
        $checkIn = RegistroHabito::create(['fk_id_habito' => $habito->id, 'data' => '2026-11-07', 'status' => 'CONCLUIDO']);
        Sanctum::actingAs($usuario);

        $this->deleteJson("/api/habits/{$habito->id}")->assertNoContent();

        $this->assertDatabaseHas('habito', ['id' => $habito->id, 'ativo' => false]);
        $this->assertDatabaseHas('registro_habito', ['id' => $checkIn->id]);
        $this->getJson("/api/habits/{$habito->id}")->assertOk()->assertJsonPath('data.ativo', false);
        $this->getJson('/api/habits/daily')->assertOk()->assertJsonCount(0, 'data');

        // Excluir de novo não falha, e o hábito pode ser reativado.
        $this->deleteJson("/api/habits/{$habito->id}")->assertNoContent();
        $this->patchJson("/api/habits/{$habito->id}", ['ativo' => true])->assertOk()->assertJsonPath('data.ativo', true);
    }

    public function test_aceita_e_valida_o_horario_do_lembrete(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/habits', $this->payload(['horario_lembrete' => '07:30']))
            ->assertCreated()
            ->assertJsonPath('data.horario_lembrete', '07:30');

        $this->postJson('/api/habits', $this->payload(['horario_lembrete' => '7h30']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['horario_lembrete']);
    }
}
