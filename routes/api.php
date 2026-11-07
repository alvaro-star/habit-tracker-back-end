<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Todo {id} das rotas é numérico; um id inválido cai em 404 antes do controller.
Route::pattern('id', '[0-9]+');

Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/refresh', [AuthController::class, 'refresh']);

require __DIR__.'/api/library.php';
require __DIR__.'/api/habits.php';
require __DIR__.'/api/check-ins.php';
require __DIR__.'/api/progression.php';
require __DIR__.'/api/dragon.php';
require __DIR__.'/api/statistics.php';
require __DIR__.'/api/notifications.php';
