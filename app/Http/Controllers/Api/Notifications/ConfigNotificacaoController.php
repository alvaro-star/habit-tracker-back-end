<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\ConfigNotificacaoRequest;
use App\Http\Resources\Notifications\ConfigNotificacaoResource;
use App\Services\ConfigNotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConfigNotificacaoController extends Controller
{
    public function __construct(protected ConfigNotificacaoService $service) {}

    /**
     * Lista as configurações do usuário, uma por gatilho.
     *
     * Um gatilho que o usuário nunca configurou vem com `ativo: true` e `horario: null`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return ConfigNotificacaoResource::collection($this->service->doUsuario($request->user()->id));
    }

    /** Liga ou desliga um gatilho e define o horário do aviso. */
    public function update(ConfigNotificacaoRequest $request, int $id): ConfigNotificacaoResource
    {
        return new ConfigNotificacaoResource($this->service->salvar($request->user()->id, $id, $request->toDto()));
    }
}
