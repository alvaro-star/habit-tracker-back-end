<?php

use App\Http\Controllers\Api\CheckIns\RegistroHabitoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Sem PUT/PATCH nem DELETE: o POST marca (cria ou atualiza) ou desmarca (exclui) o check-in do hábito no dia.
    Route::apiResource('check-ins', RegistroHabitoController::class)->parameters(['check-ins' => 'id'])->only(['index', 'store', 'show']);
});
