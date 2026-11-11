<?php

use App\Http\Controllers\Api\Statistics\StatisticController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('statistics')->group(function () {
    Route::get('/matrix', [StatisticController::class, 'matrix']);
    Route::get('/completion', [StatisticController::class, 'completion']);
    Route::get('/categories', [StatisticController::class, 'categories']);
    Route::get('/calendar', [StatisticController::class, 'calendar']);
});
