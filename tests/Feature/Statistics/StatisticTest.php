<?php

namespace Tests\Feature\Statistics;

use App\Models\Categoria;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StatisticTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_get_consistency_matrix(): void
    {
        $category = Categoria::create(['nome' => 'Saúde']);
        $habit = Habito::create([
            'fk_id_usuario' => $this->user->id,
            'fk_id_categoria' => $category->id,
            'nome' => 'Beber água',
            'valor_meta' => 2000,
        ]);

        RegistroHabito::create([
            'fk_id_habito' => $habit->id,
            'data' => now()->toDateString(),
            'status' => 'CONCLUIDO',
            'valor_realizado' => 2000,
            'percentual' => 100,
        ]);

        $response = $this->getJson('/api/statistics/matrix');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['data', 'total']]]);
    }

    public function test_can_get_completion_overview(): void
    {
        $response = $this->getJson('/api/statistics/completion');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'periodo' => ['inicio', 'fim'],
                    'concluidos',
                    'nao_concluidos',
                    'em_andamento',
                ],
            ]);
    }

    public function test_can_get_categories_overview(): void
    {
        $response = $this->getJson('/api/statistics/categories');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }
}
