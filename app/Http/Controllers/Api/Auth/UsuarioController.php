<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UsuarioResource;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    /**
     * Usuário autenticado.
     *
     * Devolve os dados do usuário logado, com o XP vitalício e o nível atual
     * (calculado pelo XP no momento da consulta).
     */
    public function show(Request $request): UsuarioResource
    {
        return new UsuarioResource($request->user());
    }
}
