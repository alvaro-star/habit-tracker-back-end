<?php

use App\Http\Controllers\Api\Progression\InsigniaController;
use App\Http\Controllers\Api\Progression\XpDiarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('progression')->name('progression.')->group(function () {
    Route::apiResource('badges', InsigniaController::class)->parameters(['badges' => 'id']);
    // Só leitura: o XP diário é calculado e gravado pelo sistema, nunca pelo usuário.
    Route::get('daily-xp', [XpDiarioController::class, 'index'])->name('daily-xp.index');
});
