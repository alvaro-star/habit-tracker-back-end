<?php

namespace Tests\Feature\Statistics;

use App\Models\Categoria;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AbstinenciaEPeriodoAnualTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-15 12:00:00');
        $this->usuario = User::factory()->create();
        Sanctum::actingAs($this->usuario);
    }

    private function habitoRuim(string $inicioAbstinencia): Habito
    {
        return Habito::create([
            'fk_id_usuario' => $this->usuario->id,
            'fk_id_categoria' => Categoria::firstOrCreate(['nome' => 'Saúde'])->id,
            'nome' => 'Fumar',
            'polaridade' => 'RUIM',
            'tipo_meta' => 'REALIZACAO',
            'frequencia' => 'DIARIA',
            'peso' => 10,
            'dificuldade' => 'MEDIO',
            'ativo' => true,
            'inicio_abstinencia' => $inicioAbstinencia,
        ]);
    }

    private function checkIn(Habito $habito, string $data, string $status = 'CONCLUIDO'): void
    {
        RegistroHabito::create([
            'fk_id_habito' => $habito->id,
            'data' => $data,
            'status' => $status,
            'percentual' => $status === 'CONCLUIDO' ? 100 : 0,
        ]);
    }

    public function test_contagem_comeca_no_inicio_abstinencia_e_coincide_com_o_habito(): void
    {
        $habito = $this->habitoRuim('2026-10-01');

        $this->getJson('/api/statistics/abstinence')
            ->assertOk()
            ->assertJsonPath('data.0.habito_id', $habito->id)
            ->assertJsonPath('data.0.marco_contagem', '2026-10-01 00:00:00')
            ->assertJsonPath('data.0.dias_consecutivos', 45)
            ->assertJsonPath('data.0.ultima_recaida', null);

        $this->getJson("/api/habits/{$habito->id}")->assertJsonPath('data.dias_sem_praticar', 45);
    }

    public function test_recaida_e_qualquer_check_in_que_nao_seja_nao_concluido(): void
    {
        $habito = $this->habitoRuim('2026-10-01');
        $this->checkIn($habito, '2026-11-10', 'EM_ANDAMENTO');
        $this->checkIn($habito, '2026-11-12', 'NAO_CONCLUIDO');

        $this->getJson('/api/statistics/abstinence')
            ->assertOk()
            ->assertJsonPath('data.0.ultima_recaida', '2026-11-10')
            ->assertJsonPath('data.0.marco_contagem', '2026-11-11 00:00:00')
            ->assertJsonPath('data.0.dias_consecutivos', 4);

        $this->getJson("/api/habits/{$habito->id}")->assertJsonPath('data.dias_sem_praticar', 4);
    }

    public function test_periodo_anual_resume_a_abstinencia_a_partir_do_inicio(): void
    {
        $habito = $this->habitoRuim('2026-03-01');
        $this->checkIn($habito, '2026-02-10'); // antes do inicio_abstinencia: não entra no período
        $this->checkIn($habito, '2026-05-05');
        $this->checkIn($habito, '2026-05-06');
        $this->checkIn($habito, '2026-08-20');

        $this->getJson('/api/statistics/abstinence?period=yearly')
            ->assertOk()
            ->assertJsonPath('data.0.dias_consecutivos', 86)
            ->assertJsonPath('data.0.periodo', [
                'data_inicio' => '2026-01-01',
                'data_fim' => '2026-12-31',
                'dias_considerados' => 260,
                'dias_com_recaida' => 3,
                'dias_limpos' => 257,
                'maior_sequencia_limpa' => 105,
                'recaidas_por_mes' => ['2026-05' => 2, '2026-08' => 1],
            ]);
    }

    public function test_periodo_anual_vale_para_as_demais_estatisticas(): void
    {
        $habito = Habito::create([
            'fk_id_usuario' => $this->usuario->id,
            'fk_id_categoria' => Categoria::firstOrCreate(['nome' => 'Estudos'])->id,
            'nome' => 'Estudar',
            'polaridade' => 'BOM',
            'tipo_meta' => 'TEMPO',
            'frequencia' => 'DIARIA',
            'valor_meta' => 60,
            'unidade' => 'minutos',
            'peso' => 15,
            'dificuldade' => 'DIFICIL',
            'ativo' => true,
        ]);
        RegistroHabito::create([
            'fk_id_habito' => $habito->id,
            'data' => '2026-01-02',
            'status' => 'CONCLUIDO',
            'valor_realizado' => 90,
            'percentual' => 100,
        ]);

        $this->getJson('/api/statistics/focus?period=yearly')->assertOk()->assertJsonPath('data.total_minutos', 90);
        $this->getJson('/api/statistics/focus?period=monthly')->assertOk()->assertJsonPath('data.total_minutos', 0);
    }
}
