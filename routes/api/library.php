<?php

use App\Http\Controllers\Api\Library\CategoriaController;
use App\Http\Controllers\Api\Library\HabitoModeloController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('library')->name('library.')->group(function () {
    Route::apiResource('categories', CategoriaController::class)->parameters(['categories' => 'id']);
    Route::apiResource('habit-templates', HabitoModeloController::class)->parameters(['habit-templates' => 'id']);
});
