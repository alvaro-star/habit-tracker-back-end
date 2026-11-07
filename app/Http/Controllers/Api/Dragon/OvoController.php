<?php

namespace App\Http\Controllers\Api\Dragon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dragon\OvoRequest;
use App\Http\Resources\Dragon\OvoResource;
use App\Services\OvoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OvoController extends Controller
{
    public function __construct(protected OvoService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return OvoResource::collection($this->service->listar());
    }

    public function store(OvoRequest $request): JsonResponse
    {
        return (new OvoResource($this->service->criar($request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id): OvoResource
    {
        return new OvoResource($this->service->buscar($id));
    }

    public function update(OvoRequest $request, int $id): OvoResource
    {
        return new OvoResource($this->service->atualizar($id, $request->toDto()));
    }

    public function destroy(int $id): Response
    {
        $this->service->excluir($id);

        return response()->noContent();
    }
}
