<?php

use App\Http\Controllers\Api\CheckIns\RegistroHabitoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('check-ins', RegistroHabitoController::class)->parameters(['check-ins' => 'id']);
});
