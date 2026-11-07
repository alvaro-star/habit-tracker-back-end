<?php

use App\Http\Controllers\Api\Progression\InsigniaController;
use App\Http\Controllers\Api\Progression\NivelController;
use App\Http\Controllers\Api\Progression\XpDiarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('progression')->name('progression.')->group(function () {
    Route::apiResource('levels', NivelController::class)->parameters(['levels' => 'id']);
    Route::apiResource('badges', InsigniaController::class)->parameters(['badges' => 'id']);
    Route::apiResource('daily-xp', XpDiarioController::class)->parameters(['daily-xp' => 'id']);
});
