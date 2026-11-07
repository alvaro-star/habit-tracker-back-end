<?php

use App\Http\Controllers\Api\Dragon\DragaoController;
use App\Http\Controllers\Api\Dragon\ElementoController;
use App\Http\Controllers\Api\Dragon\EscolhaElementoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('dragon')->name('dragon.')->group(function () {
    Route::apiResource('dragons', DragaoController::class)->parameters(['dragons' => 'id']);
    Route::apiResource('elements', ElementoController::class)->parameters(['elements' => 'id']);
    Route::apiResource('element-choices', EscolhaElementoController::class)->parameters(['element-choices' => 'id']);
});
