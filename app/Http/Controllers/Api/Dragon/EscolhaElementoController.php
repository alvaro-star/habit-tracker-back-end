<?php

namespace App\Http\Controllers\Api\Dragon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dragon\EscolhaElementoRequest;
use App\Http\Resources\Dragon\EscolhaElementoResource;
use App\Services\EscolhaElementoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EscolhaElementoController extends Controller
{
    public function __construct(protected EscolhaElementoService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return EscolhaElementoResource::collection($this->service->listarDoUsuario($request->user()->id));
    }

    /** A posse do dragão informado é validada na EscolhaElementoRequest. */
    public function store(EscolhaElementoRequest $request): JsonResponse
    {
        return (new EscolhaElementoResource($this->service->criar($request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id): EscolhaElementoResource
    {
        return new EscolhaElementoResource($this->service->buscarDoUsuario($request->user()->id, $id));
    }

    public function update(EscolhaElementoRequest $request, int $id): EscolhaElementoResource
    {
        return new EscolhaElementoResource($this->service->atualizarDoUsuario($request->user()->id, $id, $request->toDto()));
    }

    public function destroy(Request $request, int $id): Response
    {
        $this->service->excluirDoUsuario($request->user()->id, $id);

        return response()->noContent();
    }
}
