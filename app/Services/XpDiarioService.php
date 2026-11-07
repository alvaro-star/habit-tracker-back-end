<?php

namespace App\Services;

use App\Enums\Frequencia;
use App\Enums\StatusRegistro;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Repositories\RegistroHabitoRepository;
use App\Repositories\XpDiarioRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class XpDiarioService extends BaseService
{
    public function __construct(
        XpDiarioRepository $repositorio,
        protected HabitoService $habitos,
        protected RegistroHabitoRepository $registros,
    ) {
        parent::__construct($repositorio);
    }

    /**
     * XP do dia (ESCOPO.md, seção 3.3): XP = (∑W cumpridos / ∑W total) × teto diário.
     *
     * O ∑W total é a soma dos pesos dos hábitos do dia (HabitoService::habitosDiarios), sem os
     * semanais, que o escopo apura num pool próprio ao fim da semana. O ∑W
     * cumpridos vem de pesoRealizadoDoDia(). O XP é arredondado para inteiro,
     * como a coluna xp_diario.xp_ganho.
     *
     * @return array{
     *     data: string,
     *     habitos: array<int, array{id: int, nome: string, frequencia: string, peso: int, status: ?string, percentual: ?float, peso_realizado: float}>,
     *     peso_total: int,
     *     peso_realizado: float,
     *     percentual_ponderado: float,
     *     teto_diario: int,
     *     xp: int,
     * }
     */
    public function xpDoDia(int $usuarioId, CarbonInterface $data): array
    {
        $habitos = $this->habitos->habitosDiarios($usuarioId, $data)
            ->reject(fn (Habito $habito) => $habito->frequencia === Frequencia::SEMANAL->value)
            ->values();

        $pesoTotal = (int) $habitos->sum('peso');
        $realizado = $this->pesoRealizadoDoDia($habitos, $data);
        $pesoRealizado = round(array_sum(array_column($realizado, 'peso_realizado')), 2);
        $proporcao = $pesoTotal > 0 ? min($pesoRealizado / $pesoTotal, 1) : 0;
        $teto = (int) config('progression.teto_diario');

        return [
            'data' => $data->toDateString(),
            'habitos' => $habitos->map(fn (Habito $habito) => [
                'id' => $habito->id,
                'nome' => $habito->nome,
                'frequencia' => $habito->frequencia,
                'peso' => $habito->peso,
                ...$realizado[$habito->id],
            ])->all(),
            'peso_total' => $pesoTotal,
            'peso_realizado' => $pesoRealizado,
            'percentual_ponderado' => round($proporcao * 100, 2),
            'teto_diario' => $teto,
            'xp' => (int) round($proporcao * $teto),
        ];
    }

    /**
     * Peso cumprido de cada hábito na data, a partir do check-in (registro_habito):
     * CONCLUIDO soma o peso inteiro, EM_ANDAMENTO soma o peso proporcional ao
     * percentual, e NAO_CONCLUIDO ou sem check-in soma zero. Se houver mais de
     * um check-in do hábito no dia, vale o mais recente.
     *
     * @param  Collection<int, Habito>  $habitos
     * @return array<int, array{status: ?string, percentual: ?float, peso_realizado: float}> indexado pelo id do hábito
     */
    public function pesoRealizadoDoDia(Collection $habitos, CarbonInterface $data): array
    {
        $registros = $this->registros->dosHabitosNoDia($habitos->modelKeys(), $data)->keyBy('fk_id_habito');

        return $habitos->mapWithKeys(function (Habito $habito) use ($registros) {
            /** @var RegistroHabito|null $registro */
            $registro = $registros->get($habito->id);
            $percentual = $registro === null ? null : (float) $registro->percentual;

            $pesoRealizado = match ($registro?->status) {
                StatusRegistro::CONCLUIDO->value => (float) $habito->peso,
                StatusRegistro::EM_ANDAMENTO->value => round($habito->peso * $percentual / 100, 2),
                default => 0.0,
            };

            return [$habito->id => [
                'status' => $registro?->status,
                'percentual' => $percentual,
                'peso_realizado' => $pesoRealizado,
            ]];
        })->all();
    }
}
