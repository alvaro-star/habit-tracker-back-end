<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CadastroRequest;
feature/autenticacao-e-autorizacao
use App\Http\Requests\Auth\DeletarContaRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\OnboardingRequest;
use App\Http\Requests\Auth\RedefinirSenhaRequest;

use App\Http\Requests\Auth\SolicitarRedefinicaoSenhaRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthService $service) {}

    /**
     * Cadastro de usuário.
     *
     * Cria uma nova conta e devolve o par de tokens (accessToken + refreshToken)
     * e os dados do usuário. A senha deve ter no mínimo 8 caracteres e
     * deve ser confirmada com `password_confirmation`.
     *
     * @bodyParam name string required Nome do usuário.
     * @bodyParam email string required E-mail único.
     * @bodyParam password string required Senha (mín. 8 caracteres).
     * @bodyParam password_confirmation string required Confirmação da senha.
     */
    public function register(CadastroRequest $request): JsonResponse
    {
        $resultado = $this->service->registrar($request->toDto());

        return (new AuthResource($resultado, 201))->toResponse($request);
    }

    /**
     * Login.
     *
     * Autentica com e-mail e senha e devolve o par de tokens.
     *
     * @bodyParam email string required E-mail do usuário.
     * @bodyParam password string required Senha do usuário.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $resultado = $this->service->login($request->toDto());

        return (new AuthResource($resultado))->toResponse($request);
    }

    /**
     * Refresh de token.
     *
     * Revoga o token atual e emite um novo par (accessToken + refreshToken).
     * O token atual deve ser enviado como Bearer no header Authorization
     * e precisa ser um token válido do usuário autenticado.
     */
    public function refresh(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        $resultado = $this->service->refresh($request->user()->id, $token);

        return (new AuthResource($resultado))->toResponse($request);
    }

    /**
     * Logout.
     *
     * Revoga todos os tokens do usuário. O front deve descartar o
     * accessToken e o refreshToken após esta chamada.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->service->logout($request->user()->id);

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    /**
     * Marca o onboarding como concluído ou não.
     *
     * @bodyParam concluido boolean required true para concluir, false para reabrir.
     */
    public function onboarding(OnboardingRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $usuario->forceFill($request->toDto()->toArray())->save();

        return response()->json(['onboarding_concluido' => $usuario->onboarding_concluido]);
    }

    /**
     * Solicita redefinição de senha.
     *
     * Envia um e-mail com o link de redefinição. O e-mail é enviado
     * para o log (MAIL_MAILER=log) em desenvolvimento.
     *
     * @bodyParam email string required E-mail do usuário.
     */
    public function forgotPassword(SolicitarRedefinicaoSenhaRequest $request): JsonResponse
    {
        $this->service->solicitarRedefinicaoSenha($request->toDto()->email);

        return response()->json(['message' => 'Se o e-mail informado existir, você receberá um link de redefinição.']);
    }

    /**
     * Redefine a senha com o token recebido por e-mail.
     *
     * @bodyParam email string required E-mail do usuário.
     * @bodyParam token string required Token de redefinição (recebido por e-mail).
     * @bodyParam password string required Nova senha (mín. 8 caracteres).
     * @bodyParam password_confirmation string required Confirmação da nova senha.
     */
    public function resetPassword(RedefinirSenhaRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        $this->service->redefinirSenha($dto->email, $dto->token, $dto->password);

        return response()->json(['message' => 'Senha redefinida com sucesso.']);
    }

    /**
     * Exclui permanentemente a conta.
     *
     * Remove o usuário e todos os seus dados (hábitos, dragões, XP, etc.).
     * A senha é necessária para confirmar a ação.
     *
     * @bodyParam password string required Senha atual para confirmar.
     */
    public function destroyAccount(DeletarContaRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        $this->service->deletarConta($request->user()->id, $dto->password);

        return response()->json(['message' => 'Conta excluída permanentemente.']);
    }
}
