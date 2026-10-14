<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    private const EMAIL = 'admin@gmail.com';

    private const PASSWORD = 'senha123';

    private const ACCESS_TOKEN = 'static-access-token';

    private const REFRESH_TOKEN = 'static-refresh-token';

    public function login(Request $request): JsonResponse
    {
        if ($request->input('email') !== self::EMAIL || $request->input('password') !== self::PASSWORD) {
            return response()->json(['message' => 'Credenciais inválidas.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->tokens();
    }

    public function refresh(Request $request): JsonResponse
    {
        if ($request->input('refreshToken') !== self::REFRESH_TOKEN) {
            return response()->json(['message' => 'Refresh token inválido.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->tokens();
    }

    private function tokens(): JsonResponse
    {
        return response()->json([
            'accessToken' => self::ACCESS_TOKEN,
            'refreshToken' => self::REFRESH_TOKEN,
        ], Response::HTTP_OK);
    }
}
