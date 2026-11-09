<?php

namespace App\Http\Resources\Auth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Monta a resposta JSON de autenticação (login e registro).
 *
 * Estrutura raiz: `{ accessToken, refreshToken, user: { ... } }`,
 * sem embrulho `data`, porque este não é um Resource sobre um model
 * mas sim a resposta completa do endpoint.
 */
class AuthResource
{
    /** @param array{user: User, accessToken: string, refreshToken: string} $payload */
    public function __construct(
        private readonly array $payload,
        private readonly int $status = 200,
    ) {}

    public function toResponse(Request $request): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $this->payload['user'];

        return response()->json([
            'accessToken' => $this->payload['accessToken'],
            'refreshToken' => $this->payload['refreshToken'],
            'user' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'xp_vitalicio' => $usuario->xp_vitalicio,
                'fk_id_dragao' => $usuario->fk_id_dragao,
                'nivel' => $usuario->getNivel(),
                'created_at' => $usuario->created_at,
            ],
        ], $this->status);
    }
}
