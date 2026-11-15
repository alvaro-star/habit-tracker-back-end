<?php

namespace App\Services;

use App\DTOs\Auth\CadastroDTO;
use App\DTOs\Auth\LoginDTO;
use App\Exceptions\RegraDeNegocioException;
use App\Models\User;
use App\Repositories\UsuarioRepository;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Cadastro, login, refresh e logout usando Sanctum.
 *
 * O "refresh token" é um token Sanctum normal: o método refresh revoga o token
 * antigo e emite um novo, mantendo a mesma semântica que o mock provia.
 */
class AuthService
{
    public function __construct(protected UsuarioRepository $usuarios) {}

    /**
     * @return array{user: User, accessToken: string, refreshToken: string}
     */
    public function registrar(CadastroDTO $dados): array
    {
        $dadosArray = $dados->toArray();
        unset($dadosArray['password_confirmation']);

        /** @var User $usuario */
        $usuario = $this->usuarios->criar($dadosArray);

        return array_merge(
            ['user' => $usuario],
            $this->emitirParDeTokens($usuario),
        );
    }

    /**
     * @return array{user: User, accessToken: string, refreshToken: string}
     *
     * @throws RegraDeNegocioException 401
     */
    public function login(LoginDTO $dados): array
    {
        /** @var User|null $usuario */
        $usuario = User::query()->where('email', $dados->email)->first();

        if ($usuario === null || ! Hash::check($dados->password, $usuario->password)) {
            throw new RegraDeNegocioException('email', 'Credenciais inválidas.', 401);
        }

        // Atualiza o timestamp do último acesso
        $usuario->forceFill(['ultimo_acesso_at' => Carbon::now()])->save();

        return array_merge(
            ['user' => $usuario],
            $this->emitirParDeTokens($usuario),
        );
    }

    /**
     * @return array{user: User, accessToken: string, refreshToken: string}
     *
     * @throws RegraDeNegocioException 401
     */
    public function refresh(int $usuarioId, string $tokenPlainText): array
    {
        $tokenHash = hash('sha256', explode('|', $tokenPlainText, 2)[1] ?? $tokenPlainText);

        /** @var PersonalAccessToken|null $token */
        $token = PersonalAccessToken::query()
            ->where('token', $tokenHash)
            ->where('tokenable_id', $usuarioId)
            ->first();

        if ($token === null) {
            throw new RegraDeNegocioException('token', 'Token inválido.', 401);
        }

        $token->delete();

        /** @var User $usuario */
        $usuario = User::query()->findOrFail($usuarioId);

        return array_merge(
            ['user' => $usuario],
            $this->emitirParDeTokens($usuario),
        );
    }

    /**
     * Revoga todos os tokens do usuário.
     */
    public function logout(int $usuarioId): void
    {
        $this->revogarTokens($usuarioId);
    }

    /**
     * Exclui permanentemente a conta do usuário e todos os seus dados.
     *
     * 1. Confirma a senha
     * 2. Revoga todos os tokens
     * 3. Deleta o usuário (as FKs com cascadeOnDelete cuidam do resto)
     *
     * @throws RegraDeNegocioException 401 se a senha não conferir
     */
    public function deletarConta(int $usuarioId, string $password): void
    {
        /** @var User $usuario */
        $usuario = User::query()->findOrFail($usuarioId);

        if (! Hash::check($password, $usuario->password)) {
            throw new RegraDeNegocioException('password', 'Senha incorreta.', 401);
        }

        $this->revogarTokens($usuarioId);

        $usuario->delete();
    }

    /**
     * Revoga todos os tokens Sanctum do usuário.
     */
    private function revogarTokens(int $usuarioId): void
    {
        User::query()->findOrFail($usuarioId)->tokens()->delete();
    }

    /**
     * Envia o e-mail com o link de redefinição de senha.
     *
     * Não revela se o e-mail existe ou não por segurança: sempre retorna sucesso.
     */
    public function solicitarRedefinicaoSenha(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    /**
     * Redefine a senha com o token enviado por e-mail.
     *
     * @throws RegraDeNegocioException 400 se o token for inválido/expirado
     */
    public function redefinirSenha(string $email, string $token, string $novaSenha): void
    {
        $status = Password::reset(
            ['email' => $email, 'token' => $token, 'password' => $novaSenha, 'password_confirmation' => $novaSenha],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new RegraDeNegocioException(
                'token',
                __($status),
                400,
            );
        }
    }

    /**
     * @return array{accessToken: string, refreshToken: string}
     */
    private function emitirParDeTokens(User $usuario): array
    {
        $accessToken = $usuario->createToken('access-token')->plainTextToken;
        $refreshToken = $usuario->createToken('refresh-token')->plainTextToken;

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ];
    }
}
