<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\OnboardingRequest;
use App\Http\Resources\Onboarding\OnboardingResource;
use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OnboardingController extends Controller
{
    public function __construct(protected OnboardingService $service) {}

    /**
     * Situação do onboarding.
     *
     * `concluido: false` indica o primeiro acesso: o front-end deve apresentar o
     * fluxo de onboarding. Depois de concluído, traz os objetivos, o dragão ativo
     * (com o ovo escolhido) e os hábitos do usuário.
     */
    public function show(Request $request): OnboardingResource
    {
        return new OnboardingResource($this->service->situacao($request->user()));
    }

    /**
     * Conclui o onboarding.
     *
     * Recebe de uma vez as respostas do fluxo: grava o nome e os objetivos do
     * usuário, cria o dragão ativo com o ovo escolhido (nascido hoje, com 0 XP) e
     * um hábito diário para cada modelo da biblioteca. Só pode ser feito uma vez:
     * uma segunda chamada devolve 409. Se o usuário já tiver um dragão ativo,
     * devolve 422 em `status` e nada é gravado.
     */
    public function store(OnboardingRequest $request): JsonResponse
    {
        return (new OnboardingResource($this->service->concluir($request->user(), $request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
