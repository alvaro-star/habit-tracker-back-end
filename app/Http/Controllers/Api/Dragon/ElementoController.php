<?php

namespace App\Http\Controllers\Api\Dragon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dragon\ElementoRequest;
use App\Http\Resources\Dragon\ElementoResource;
use App\Services\ElementoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ElementoController extends Controller
{
    public function __construct(protected ElementoService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return ElementoResource::collection($this->service->listar());
    }

    public function store(ElementoRequest $request): JsonResponse
    {
        return (new ElementoResource($this->service->criar($request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id): ElementoResource
    {
        return new ElementoResource($this->service->buscar($id));
    }

    public function update(ElementoRequest $request, int $id): ElementoResource
    {
        return new ElementoResource($this->service->atualizar($id, $request->toDto()));
    }

    public function destroy(int $id): Response
    {
        $this->service->excluir($id);

        return response()->noContent();
    }
}
