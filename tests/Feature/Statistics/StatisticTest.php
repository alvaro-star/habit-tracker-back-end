<?php

namespace Tests\Feature\Statistics;

use App\Models\Category;
use App\Models\Habit;
use App\Models\HabitRecord;
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
        $category = Category::create(['nome' => 'Saúde']);
        $habit = Habit::create([
            'fk_id_usuario' => $this->user->id,
            'fk_id_categoria' => $category->id,
            'nome' => 'Beber água',
            'valor_meta' => 2000,
        ]);

        HabitRecord::create([
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

    public function test_can_filter_by_period(): void
    {
        $response = $this->getJson('/api/statistics/completion?period=weekly');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['periodo', 'concluidos', 'nao_concluidos']]);
    }

    public function test_can_get_calendar_data(): void
    {
        $response = $this->getJson('/api/statistics/calendar?year=2026&month=11');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'ano',
                'mes',
                'dias',
            ]);
    }
}
