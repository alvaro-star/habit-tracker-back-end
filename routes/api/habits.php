<?php

use App\Http\Controllers\Api\Habits\HabitoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('habits/daily', [HabitoController::class, 'daily']);
    Route::apiResource('habits', HabitoController::class)->parameters(['habits' => 'id']);
});
