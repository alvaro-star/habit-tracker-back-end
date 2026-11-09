<?php

use App\Http\Controllers\Api\Auth\UsuarioController;
use Illuminate\Support\Facades\Route;

// Todo {id} das rotas é numérico; um id inválido cai em 404 antes do controller.
Route::pattern('id', '[0-9]+');

require __DIR__.'/api/auth.php';

Route::get('/user', [UsuarioController::class, 'show'])->middleware('auth:sanctum');

require __DIR__.'/api/library.php';
require __DIR__.'/api/habits.php';
require __DIR__.'/api/check-ins.php';
require __DIR__.'/api/progression.php';
require __DIR__.'/api/dragon.php';
require __DIR__.'/api/statistics.php';
require __DIR__.'/api/notifications.php';
