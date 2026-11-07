<?php

use App\Http\Controllers\Api\Notifications\GatilhoNotificacaoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('notifications')->name('notifications.')->group(function () {
    Route::apiResource('triggers', GatilhoNotificacaoController::class)->parameters(['triggers' => 'id']);
});
