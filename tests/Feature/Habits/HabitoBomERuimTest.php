<?php

namespace Tests\Feature\Habits;

use App\Models\Categoria;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Services\HabitoService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Hábitos bons e ruins: meta positiva (MOD3-TASK3) e contador de abstinência (MOD3-TASK4). */
class HabitoBomERuimTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00:00'));
        $this->usuario = User::factory()->create();
        Sanctum::actingAs($this->usuario);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'fk_id_categoria' => Categoria::factory()->create()->id,
            'nome' => 'Correr',
            'polaridade' => 'BOM',
            'tipo_meta' => 'TEMPO',
            'frequencia' => 'DIARIA',
            'dia_frequencia' => null,
            'peso' => 10,
        ], $extra);
    }

    private function recaida(Habito $habito, string $data, string $status = 'CONCLUIDO'): void
    {
        RegistroHabito::create(['fk_id_habito' => $habito->id, 'data' => $data, 'status' => $status]);
    }

    // MOD3-TASK3: metas positivas para hábitos bons

    public function test_habito_bom_cadastra_meta_positiva(): void
    {
        $this->postJson('/api/habits', $this->payload(['valor_meta' => 30]))
            ->assertCreated()
            ->assertJsonPath('data.polaridade', 'BOM')
            ->assertJsonPath('data.valor_meta', '30.00')
            ->assertJsonPath('data.inicio_abstinencia', null)
            ->assertJsonPath('data.dias_sem_praticar', null);
    }

    public function test_meta_deve_ser_maior_que_zero(): void
    {
        $this->postJson('/api/habits', $this->payload(['valor_meta' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('valor_meta');
    }

    public function test_service_trata_meta_zero_como_sem_meta(): void
    {
        /** @var Habito $habito */
        $habito = app(HabitoService::class)->criarParaUsuario($this->usuario->id, $this->payload(['valor_meta' => 0]));

        $this->assertNull($habito->valor_meta);
    }

    public function test_realizacao_nao_tem_meta_numerica(): void
    {
        $this->postJson('/api/habits', $this->payload(['tipo_meta' => 'REALIZACAO', 'valor_meta' => 5]))
            ->assertCreated()
            ->assertJsonPath('data.valor_meta', null);
    }

    public function test_habito_ruim_nao_aceita_meta_positiva(): void
    {
        $this->postJson('/api/habits', $this->payload(['polaridade' => 'RUIM', 'valor_meta' => 10]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('valor_meta');

        $habito = Habito::factory()->doUsuario($this->usuario)->ruim()->create();

        $this->patchJson("/api/habits/{$habito->id}", ['valor_meta' => 10])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('valor_meta');
    }

    public function test_habito_que_vira_ruim_perde_a_meta(): void
    {
        $habito = Habito::factory()->doUsuario($this->usuario)->deTempo(30)->create();

        $this->patchJson("/api/habits/{$habito->id}", ['polaridade' => 'RUIM'])
            ->assertOk()
            ->assertJsonPath('data.valor_meta', null);
    }

    // MOD3-TASK4: tempo ou dias sem praticar um hábito ruim

    public function test_habito_ruim_comeca_a_contar_no_cadastro(): void
    {
        $this->postJson('/api/habits', $this->payload(['polaridade' => 'RUIM', 'tipo_meta' => 'REALIZACAO']))
            ->assertCreated()
            ->assertJsonPath('data.inicio_abstinencia', fn ($inicio) => str_starts_with($inicio, '2026-11-15'))
            ->assertJsonPath('data.ultima_recaida', null)
            ->assertJsonPath('data.dias_sem_praticar', 0);

        $this->travel(3)->days();

        $this->getJson('/api/habits')->assertOk()->assertJsonPath('data.0.dias_sem_praticar', 3);
    }

    public function test_usuario_informa_desde_quando_esta_sem_praticar(): void
    {
        $this->postJson('/api/habits', $this->payload([
            'polaridade' => 'RUIM',
            'tipo_meta' => 'REALIZACAO',
            'inicio_abstinencia' => '2026-11-05',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.dias_sem_praticar', 10);
    }

    public function test_inicio_da_abstinencia_nao_pode_ser_no_futuro(): void
    {
        $this->postJson('/api/habits', $this->payload(['polaridade' => 'RUIM', 'inicio_abstinencia' => '2026-11-20']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('inicio_abstinencia');
    }

    public function test_recaida_reinicia_a_contagem(): void
    {
        $habito = Habito::factory()->doUsuario($this->usuario)->ruim('2026-11-01')->create();
        $this->recaida($habito, '2026-11-10');
        // Um check-in NAO_CONCLUIDO não é recaída.
        $this->recaida($habito, '2026-11-12', 'NAO_CONCLUIDO');

        $this->getJson("/api/habits/{$habito->id}")
            ->assertOk()
            ->assertJsonPath('data.ultima_recaida', '2026-11-10')
            ->assertJsonPath('data.dias_sem_praticar', 4);

        $this->recaida($habito, '2026-11-15');

        $this->getJson("/api/habits/{$habito->id}")->assertJsonPath('data.dias_sem_praticar', 0);
    }

    public function test_recaida_anterior_ao_inicio_informado_nao_conta(): void
    {
        $habito = Habito::factory()->doUsuario($this->usuario)->ruim('2026-11-08')->create();
        $this->recaida($habito, '2026-11-03');

        $this->getJson("/api/habits/{$habito->id}")->assertJsonPath('data.dias_sem_praticar', 7);
    }

    public function test_trocar_a_polaridade_liga_e_desliga_o_contador(): void
    {
        $habito = Habito::factory()->doUsuario($this->usuario)->create();

        $this->patchJson("/api/habits/{$habito->id}", ['polaridade' => 'RUIM'])
            ->assertOk()
            ->assertJsonPath('data.dias_sem_praticar', 0);
        $this->assertNotNull($habito->refresh()->inicio_abstinencia);

        // Editar outro campo não reinicia a contagem.
        $this->travel(2)->days();
        $this->patchJson("/api/habits/{$habito->id}", ['nome' => 'Fumar'])
            ->assertOk()
            ->assertJsonPath('data.dias_sem_praticar', 2);

        $this->patchJson("/api/habits/{$habito->id}", ['polaridade' => 'BOM', 'inicio_abstinencia' => '2026-11-01'])
            ->assertOk()
            ->assertJsonPath('data.inicio_abstinencia', null)
            ->assertJsonPath('data.dias_sem_praticar', null);
    }

    public function test_listagem_traz_a_ultima_recaida_de_cada_habito(): void
    {
        $fumar = Habito::factory()->doUsuario($this->usuario)->ruim('2026-11-01')->create();
        $roer = Habito::factory()->doUsuario($this->usuario)->ruim('2026-11-01')->create();
        $this->recaida($fumar, '2026-11-13');

        $resposta = $this->getJson('/api/habits')->assertOk();

        $dias = collect($resposta->json('data'))->pluck('dias_sem_praticar', 'id');
        $this->assertSame(1, $dias[$fumar->id]);
        $this->assertSame(14, $dias[$roer->id]);
    }
}
