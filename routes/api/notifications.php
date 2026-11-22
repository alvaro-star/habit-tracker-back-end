<?php

use App\Http\Controllers\Api\Notifications\ConfigNotificacaoController;
use App\Http\Controllers\Api\Notifications\NotificacaoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificacaoController::class, 'index'])->name('index');
    Route::post('/', [NotificacaoController::class, 'store'])->name('store');
    Route::get('settings', [ConfigNotificacaoController::class, 'index'])->name('settings.index');
});
