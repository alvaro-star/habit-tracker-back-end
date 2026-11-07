<?php

namespace Tests\Feature\Progression;

use App\Models\Categoria;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Services\XpDiarioService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XpDoDiaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private int $categoriaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();
        $this->categoriaId = Categoria::create(['nome' => 'Geral'])->id;
        $this->travelTo('2026-01-01');
    }

    private function habito(string $nome, string $dificuldade, int $peso, string $frequencia = 'DIARIA', ?string $dia = null): Habito
    {
        return Habito::create([
            'fk_id_usuario' => $this->usuario->id,
            'fk_id_categoria' => $this->categoriaId,
            'nome' => $nome,
            'frequencia' => $frequencia,
            'dia_frequencia' => $dia,
            'dificuldade' => $dificuldade,
            'peso' => $peso,
        ]);
    }

    private function checkIn(Habito $habito, string $status, float $percentual = 100, string $data = '2026-11-09'): void
    {
        RegistroHabito::create(['fk_id_habito' => $habito->id, 'data' => $data, 'status' => $status, 'percentual' => $percentual]);
    }

    private function xpDoDia(string $data = '2026-11-09'): array
    {
        return app(XpDiarioService::class)->xpDoDia($this->usuario->id, CarbonImmutable::parse($data));
    }

    public function test_calcula_o_xp_ponderado_com_concluidos_e_em_andamento(): void
    {
        $agua = $this->habito('Beber água', 'FACIL', 5);
        $estudar = $this->habito('Estudar', 'DIFICIL', 15);
        $treino = $this->habito('Treinar', 'MUITO_DIFICIL', 20, 'MENSAL', '9');
        $ler = $this->habito('Ler', 'MEDIO', 10);

        $this->checkIn($agua, 'CONCLUIDO');
        $this->checkIn($estudar, 'EM_ANDAMENTO', 40);
        $this->checkIn($treino, 'NAO_CONCLUIDO', 0);
        $this->checkIn($ler, 'CONCLUIDO', 100, '2026-11-08'); // outro dia: não conta

        $xp = $this->xpDoDia();

        // ∑W total = 5 + 15 + 20 + 10 = 50; ∑W cumpridos = 5 + 15 × 40% = 11; XP = 11/50 × 25 = 5,5 → 6
        $this->assertSame(50, $xp['peso_total']);
        $this->assertSame(11.0, $xp['peso_realizado']);
        $this->assertSame(22.0, $xp['percentual_ponderado']);
        $this->assertSame(6, $xp['xp']);
        $this->assertSame([5, 15, 20, 10], array_column($xp['habitos'], 'peso'));
        $this->assertSame(['CONCLUIDO', 'EM_ANDAMENTO', 'NAO_CONCLUIDO', null], array_column($xp['habitos'], 'status'));
        $this->assertSame([5.0, 6.0, 0.0, 0.0], array_column($xp['habitos'], 'peso_realizado'));
    }

    public function test_dia_perfeito_rende_o_teto_diario(): void
    {
        $this->checkIn($this->habito('Beber água', 'FACIL', 5), 'CONCLUIDO');
        $this->checkIn($this->habito('Estudar', 'DIFICIL', 15), 'CONCLUIDO');

        $this->assertSame(25, $this->xpDoDia()['xp']);
    }

    public function test_habito_semanal_entra_no_xp_do_seu_dia_da_semana(): void
    {
        $this->checkIn($this->habito('Beber água', 'FACIL', 5), 'CONCLUIDO');
        $faxina = $this->habito('Faxina', 'MUITO_DIFICIL', 20, 'SEMANAL', 'SEG');
        $this->checkIn($faxina, 'NAO_CONCLUIDO', 0);

        // 2026-11-09 é segunda-feira: a faxina entra no total (5 + 20) e não foi cumprida.
        $segunda = $this->xpDoDia('2026-11-09');
        $this->assertSame(25, $segunda['peso_total']);
        $this->assertSame(['Beber água', 'Faxina'], array_column($segunda['habitos'], 'nome'));
        $this->assertSame(5, $segunda['xp']); // 5/25 × 25

        // Cumprir a faxina na segunda completa o dia.
        $this->checkIn($faxina, 'CONCLUIDO');
        $this->assertSame(25, $this->xpDoDia('2026-11-09')['xp']);

        // Na terça, a faxina não cai: só a água conta.
        $this->checkIn($this->habito('Beber água 2', 'FACIL', 5), 'CONCLUIDO', 100, '2026-11-10');
        $terca = $this->xpDoDia('2026-11-10');
        $this->assertNotContains('Faxina', array_column($terca['habitos'], 'nome'));
    }

    public function test_habito_mensal_entra_no_xp_do_seu_dia_do_mes(): void
    {
        $this->checkIn($this->habito('Contas do mês', 'MEDIO', 10, 'MENSAL', '9'), 'CONCLUIDO');

        $this->assertSame(10, $this->xpDoDia('2026-11-09')['peso_total']);
        $this->assertSame(0, $this->xpDoDia('2026-11-10')['peso_total']);
    }

    public function test_vale_o_check_in_mais_recente_do_dia(): void
    {
        $estudar = $this->habito('Estudar', 'DIFICIL', 15);
        $this->checkIn($estudar, 'EM_ANDAMENTO', 20);
        $this->checkIn($estudar, 'CONCLUIDO');

        $this->assertSame(15.0, $this->xpDoDia()['peso_realizado']);
    }

    public function test_habito_criado_depois_da_data_nao_entra_no_xp_do_dia(): void
    {
        $this->checkIn($this->habito('Beber água', 'FACIL', 5), 'CONCLUIDO');

        // Criado bem depois de 2026-11-09: não existia naquele dia.
        $this->travelTo('2026-12-15');
        $this->habito('Estudar', 'MUITO_DIFICIL', 20);

        $xp = $this->xpDoDia('2026-11-09');

        $this->assertSame(5, $xp['peso_total']);
        $this->assertSame(['Beber água'], array_column($xp['habitos'], 'nome'));
        $this->assertSame(25, $xp['xp']);
    }

    public function test_dia_sem_habitos_rende_zero_xp(): void
    {
        $xp = $this->xpDoDia();

        $this->assertSame(0, $xp['peso_total']);
        $this->assertSame(0, $xp['xp']);
        $this->assertSame([], $xp['habitos']);
    }
}
