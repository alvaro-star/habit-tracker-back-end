<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use App\Services\Statistics\StatisticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    public function __construct(
        protected StatisticService $statisticService
    ) {}

    public function matrix(Request $request): JsonResponse
    {
        $data = $this->statisticService->getConsistencyMatrix(
            $request->user()->id,
            $request->query('period'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json(['data' => $data]);
    }

    public function completion(Request $request): JsonResponse
    {
        $data = $this->statisticService->getCompletionOverview(
            $request->user()->id,
            $request->query('period'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json(['data' => $data]);
    }

    public function categories(Request $request): JsonResponse
    {
        $data = $this->statisticService->getGoalVsActualByCategory(
            $request->user()->id,
            $request->query('period'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json(['data' => $data]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $year = (int) ($request->query('year') ?? now()->year);
        $month = (int) ($request->query('month') ?? now()->month);

        $data = $this->statisticService->getCalendarData(
            $request->user()->id,
            $year,
            $month
        );

        return response()->json([
            'ano' => $year,
            'mes' => $month,
            'dias' => $data,
        ]);
    }

    /**
     * Contador de abstinência dos hábitos ruins.
     *
     * A contagem começa no `inicio_abstinencia` do hábito ou no dia seguinte à última
     * recaída (qualquer check-in que não seja `NAO_CONCLUIDO`), o que for mais recente:
     * é o mesmo `dias_sem_praticar` de `GET /api/habits`. O bloco `periodo` resume o
     * intervalo de `period` (`daily`, `weekly`, `monthly`, `yearly`) ou
     * `start_date`/`end_date` (padrão: últimos 30 dias), contando só os dias a partir
     * do `inicio_abstinencia` e até hoje.
     */
    public function abstinence(Request $request): JsonResponse
    {
        $data = $this->statisticService->getAbstinenceIndicators(
            $request->user()->id,
            $request->query('period'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json(['data' => $data]);
    }

    public function focus(Request $request): JsonResponse
    {
        $data = $this->statisticService->getFocusPanel(
            $request->user()->id,
            $request->query('period'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json(['data' => $data]);
    }
}
