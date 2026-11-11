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
}
