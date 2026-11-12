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
        $userId = $request->user()->id;
        $data = $this->statisticService->getConsistencyMatrix(
            $userId,
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json(['data' => $data]);
    }

    public function completion(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $data = $this->statisticService->getCompletionOverview(
            $userId,
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json(['data' => $data]);
    }

    public function categories(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $data = $this->statisticService->getGoalVsActualByCategory(
            $userId,
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json(['data' => $data]);
    }

    public function abstinence(Request $request): JsonResponse
    {
        $data = $this->statisticService->getAbstinenceIndicators(
            $request->user()->id
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
