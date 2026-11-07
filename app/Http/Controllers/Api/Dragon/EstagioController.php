<?php

namespace App\Http\Controllers\Api\Dragon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dragon\EstagioRequest;
use App\Http\Resources\Dragon\EstagioResource;
use App\Services\EstagioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EstagioController extends Controller
{
    public function __construct(protected EstagioService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return EstagioResource::collection($this->service->listar());
    }

    public function store(EstagioRequest $request): JsonResponse
    {
        return (new EstagioResource($this->service->criar($request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id): EstagioResource
    {
        return new EstagioResource($this->service->buscar($id));
    }

    public function update(EstagioRequest $request, int $id): EstagioResource
    {
        return new EstagioResource($this->service->atualizar($id, $request->toDto()));
    }

    public function destroy(int $id): Response
    {
        $this->service->excluir($id);

        return response()->noContent();
    }
}
