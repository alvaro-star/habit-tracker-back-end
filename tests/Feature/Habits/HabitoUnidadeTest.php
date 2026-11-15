<?php

namespace Tests\Feature\Habits;

use App\Models\Categoria;
use App\Models\Habito;
use App\Models\User;
use App\Services\HabitoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Unidade customizada nas metas de quantidade (MOD3-TASK8). */
class HabitoUnidadeTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = User::factory()->create();
        Sanctum::actingAs($this->usuario);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'fk_id_categoria' => Categoria::factory()->create()->id,
            'nome' => 'Beber água',
            'polaridade' => 'BOM',
            'tipo_meta' => 'QUANTIDADE',
            'frequencia' => 'DIARIA',
            'dia_frequencia' => null,
            'valor_meta' => 8,
            'unidade' => 'copos',
            'peso' => 5,
        ], $extra);
    }

    public function test_quantidade_aceita_unidade_definida_pelo_usuario(): void
    {
        $this->postJson('/api/habits', $this->payload(['unidade' => '  páginas  ']))
            ->assertCreated()
            ->assertJsonPath('data.tipo_meta', 'QUANTIDADE')
            ->assertJsonPath('data.unidade', 'páginas');
    }

    public function test_quantidade_pode_ficar_sem_unidade(): void
    {
        $payload = $this->payload();
        unset($payload['unidade']);

        $this->postJson('/api/habits', $payload)
            ->assertCreated()
            ->assertJsonPath('data.unidade', null);
    }

    public function test_unidade_tem_no_maximo_20_caracteres(): void
    {
        $this->postJson('/api/habits', $this->payload(['unidade' => str_repeat('a', 21)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('unidade');
    }

    /** @return array<string, array{string, ?string}> */
    public static function tiposSemUnidadeCustomizada(): array
    {
        return [
            'tempo usa minutos' => ['TEMPO', 'minutos'],
            'realização fica sem unidade' => ['REALIZACAO', null],
        ];
    }

    #[DataProvider('tiposSemUnidadeCustomizada')]
    public function test_unidade_enviada_e_descartada_fora_da_quantidade(string $tipo, ?string $unidade): void
    {
        $this->postJson('/api/habits', $this->payload(['tipo_meta' => $tipo, 'unidade' => 'copos']))
            ->assertCreated()
            ->assertJsonPath('data.unidade', $unidade);
    }

    public function test_patch_muda_a_unidade_de_um_habito_de_quantidade(): void
    {
        $habito = Habito::factory()->doUsuario($this->usuario)->deQuantidade(2, 'litros')->create();

        $this->patchJson("/api/habits/{$habito->id}", ['unidade' => 'copos'])
            ->assertOk()
            ->assertJsonPath('data.unidade', 'copos');
    }

    public function test_patch_so_da_unidade_num_habito_de_tempo_mantem_minutos(): void
    {
        $habito = Habito::factory()->doUsuario($this->usuario)->deTempo()->create();

        $this->patchJson("/api/habits/{$habito->id}", ['unidade' => 'horas'])
            ->assertOk()
            ->assertJsonPath('data.unidade', 'minutos');
    }

    public function test_trocar_o_tipo_de_meta_acerta_a_unidade(): void
    {
        $habito = Habito::factory()->doUsuario($this->usuario)->deQuantidade(2, 'litros')->create();

        $this->patchJson("/api/habits/{$habito->id}", ['tipo_meta' => 'TEMPO'])
            ->assertOk()
            ->assertJsonPath('data.unidade', 'minutos');

        // Ao voltar para QUANTIDADE sem enviar a unidade, os minutos não ficam para trás.
        $this->patchJson("/api/habits/{$habito->id}", ['tipo_meta' => 'QUANTIDADE'])
            ->assertOk()
            ->assertJsonPath('data.unidade', null);

        $this->patchJson("/api/habits/{$habito->id}", ['tipo_meta' => 'REALIZACAO', 'unidade' => 'km'])
            ->assertOk()
            ->assertJsonPath('data.unidade', null);
    }

    public function test_service_aplica_a_regra_sem_passar_pela_request(): void
    {
        /** @var Habito $habito */
        $habito = app(HabitoService::class)->criarParaUsuario($this->usuario->id, [
            ...$this->payload(['tipo_meta' => 'TEMPO', 'unidade' => 'min']),
            'dia_frequencia' => null,
        ]);

        $this->assertSame('minutos', $habito->unidade);
    }
}
