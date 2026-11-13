<?php

use App\Http\Controllers\Api\Dragon\DragaoController;
use App\Http\Controllers\Api\Dragon\EscolhaElementoController;
use App\Http\Controllers\Api\Dragon\SantuarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('dragon')->name('dragon.')->group(function () {
    Route::apiResource('dragons', DragaoController::class)->parameters(['dragons' => 'id']);
    Route::apiResource('element-choices', EscolhaElementoController::class)->parameters(['element-choices' => 'id']);
});

Route::middleware('auth:sanctum')->prefix('sanctuary')->name('sanctuary.')->group(function () {
    Route::get('/', [SantuarioController::class, 'index'])->name('index');
    Route::get('{id}', [SantuarioController::class, 'show'])->name('show');
});
