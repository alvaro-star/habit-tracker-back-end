<?php

namespace App\Services\Statistics;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticService
{
    /**
     * Resolve datas de início e fim com base no período (MOD7-TASK7)
     * Opções de período: 'daily' (diário), 'weekly' (semanal), 'monthly' (mensal)
     */
    private function resolvePeriodDates(?string $period, ?string $startDate, ?string $endDate): array
    {
        $now = Carbon::now();

        if ($period === 'daily') {
            return [$now->toDateString(), $now->toDateString()];
        }

        if ($period === 'weekly') {
            return [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()];
        }

        if ($period === 'monthly') {
            return [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()];
        }

        $start = $startDate ? Carbon::parse($startDate)->toDateString() : Carbon::now()->subDays(30)->toDateString();
        $end = $endDate ? Carbon::parse($endDate)->toDateString() : Carbon::now()->toDateString();

        return [$start, $end];
    }

    /**
     * MOD7-TASK1 e TASK4: Matriz de consistência diária (Heatmap GitHub)
     */
    public function getConsistencyMatrix(int $userId, ?string $period = null, ?string $startDate = null, ?string $endDate = null): array
    {
        [$start, $end] = $this->resolvePeriodDates($period, $startDate, $endDate);

        return DB::table('registro_habito')
            ->join('habito', 'registro_habito.fk_id_habito', '=', 'habito.id')
            ->where('habito.fk_id_usuario', $userId)
            ->whereBetween('registro_habito.data', [$start, $end])
            ->where('registro_habito.status', 'CONCLUIDO')
            ->select('registro_habito.data', DB::raw('COUNT(*) as total_concluidos'))
            ->groupBy('registro_habito.data')
            ->orderBy('registro_habito.data', 'ASC')
            ->get()
            ->map(fn ($row) => [
                'data' => $row->data,
                'total' => (int) $row->total_concluidos,
            ])
            ->toArray();
    }

    /**
     * MOD7-TASK2 e TASK5: Barras empilhadas Realizado vs Não Realizado
     */
    public function getCompletionOverview(int $userId, ?string $period = null, ?string $startDate = null, ?string $endDate = null): array
    {
        [$start, $end] = $this->resolvePeriodDates($period, $startDate, $endDate);

        $result = DB::table('registro_habito')
            ->join('habito', 'registro_habito.fk_id_habito', '=', 'habito.id')
            ->where('habito.fk_id_usuario', $userId)
            ->whereBetween('registro_habito.data', [$start, $end])
            ->selectRaw("
                SUM(CASE WHEN registro_habito.status = 'CONCLUIDO' THEN 1 ELSE 0 END) as total_concluido,
                SUM(CASE WHEN registro_habito.status = 'NAO_CONCLUIDO' THEN 1 ELSE 0 END) as total_nao_concluido,
                SUM(CASE WHEN registro_habito.status = 'EM_ANDAMENTO' THEN 1 ELSE 0 END) as total_em_andamento
            ")
            ->first();

        return [
            'periodo' => ['inicio' => $start, 'fim' => $end],
            'concluidos' => (int) ($result->total_concluido ?? 0),
            'nao_concluidos' => (int) ($result->total_nao_concluido ?? 0),
            'em_andamento' => (int) ($result->total_em_andamento ?? 0),
        ];
    }

    /**
     * MOD7-TASK3 e TASK6: Meta vs Realizado agrupado por Categoria
     */
    public function getGoalVsActualByCategory(int $userId, ?string $period = null, ?string $startDate = null, ?string $endDate = null): array
    {
        [$start, $end] = $this->resolvePeriodDates($period, $startDate, $endDate);

        return DB::table('categoria')
            ->join('habito', 'categoria.id', '=', 'habito.fk_id_categoria')
            ->leftJoin('registro_habito', function ($join) use ($start, $end) {
                $join->on('habito.id', '=', 'registro_habito.fk_id_habito')
                    ->whereBetween('registro_habito.data', [$start, $end]);
            })
            ->where('habito.fk_id_usuario', $userId)
            ->where('habito.ativo', true)
            ->select(
                'categoria.id as categoria_id',
                'categoria.nome as categoria_nome',
                DB::raw('COALESCE(SUM(habito.valor_meta), 0) as total_meta'),
                DB::raw('COALESCE(SUM(registro_habito.valor_realizado), 0) as total_realizado')
            )
            ->groupBy('categoria.id', 'categoria.nome')
            ->get()
            ->map(fn ($row) => [
                'categoria_id' => $row->categoria_id,
                'categoria' => $row->categoria_nome,
                'meta' => (float) $row->total_meta,
                'realizado' => (float) $row->total_realizado,
            ])
            ->toArray();
    }

    /**
     * MOD7-TASK8: Dados para o calendário integrado
     * Retorna os dias do mês com contagem e percentual médio de conclusão
     */
    public function getCalendarData(int $userId, int $year, int $month): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        return DB::table('registro_habito')
            ->join('habito', 'registro_habito.fk_id_habito', '=', 'habito.id')
            ->where('habito.fk_id_usuario', $userId)
            ->whereBetween('registro_habito.data', [$start, $end])
            ->select(
                'registro_habito.data',
                DB::raw("SUM(CASE WHEN registro_habito.status = 'CONCLUIDO' THEN 1 ELSE 0 END) as concluidos"),
                DB::raw('COUNT(*) as total_registros'),
                DB::raw('AVG(registro_habito.percentual) as taxa_media_cumprimento')
            )
            ->groupBy('registro_habito.data')
            ->orderBy('registro_habito.data', 'ASC')
            ->get()
            ->map(fn ($row) => [
                'data' => $row->data,
                'concluidos' => (int) $row->concluidos,
                'total' => (int) $row->total_registros,
                'taxa_cumprimento' => round((float) $row->taxa_media_cumprimento, 2),
            ])
            ->toArray();
    }
}
