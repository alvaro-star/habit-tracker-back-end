<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\GatilhoNotificacaoRequest;
use App\Http\Resources\Notifications\GatilhoNotificacaoResource;
use App\Services\GatilhoNotificacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GatilhoNotificacaoController extends Controller
{
    public function __construct(protected GatilhoNotificacaoService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return GatilhoNotificacaoResource::collection($this->service->listar());
    }

    public function store(GatilhoNotificacaoRequest $request): JsonResponse
    {
        return (new GatilhoNotificacaoResource($this->service->criar($request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id): GatilhoNotificacaoResource
    {
        return new GatilhoNotificacaoResource($this->service->buscar($id));
    }

    public function update(GatilhoNotificacaoRequest $request, int $id): GatilhoNotificacaoResource
    {
        return new GatilhoNotificacaoResource($this->service->atualizar($id, $request->toDto()));
    }

    public function destroy(int $id): Response
    {
        $this->service->excluir($id);

        return response()->noContent();
    }
}
