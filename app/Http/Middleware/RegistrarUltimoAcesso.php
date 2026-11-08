<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Repositories\UsuarioRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Grava em users.ultimo_acesso quando o usuário autenticado usou a API pela última vez. */
class RegistrarUltimoAcesso
{
    /** Evita uma escrita no banco a cada requisição. */
    private const INTERVALO_MINIMO_EM_MINUTOS = 5;

    public function __construct(protected UsuarioRepository $usuarios) {}

    public function handle(Request $request, Closure $next): Response
    {
        $resposta = $next($request);
        $usuario = $request->user('sanctum');

        if ($usuario instanceof User && ($usuario->ultimo_acesso === null
            || $usuario->ultimo_acesso->lte(now()->subMinutes(self::INTERVALO_MINIMO_EM_MINUTOS)))) {
            $this->usuarios->registrarAcesso($usuario->id);
        }

        return $resposta;
    }
}
