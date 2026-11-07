<?php

namespace App\Http\Controllers\Api\Progression;

use App\Http\Controllers\Controller;
use App\Http\Requests\Progression\NivelRequest;
use App\Http\Resources\Progression\NivelResource;
use App\Services\NivelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NivelController extends Controller
{
    public function __construct(protected NivelService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return NivelResource::collection($this->service->listar());
    }

    public function store(NivelRequest $request): JsonResponse
    {
        return (new NivelResource($this->service->criar($request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id): NivelResource
    {
        return new NivelResource($this->service->buscar($id));
    }

    public function update(NivelRequest $request, int $id): NivelResource
    {
        return new NivelResource($this->service->atualizar($id, $request->toDto()));
    }

    public function destroy(int $id): Response
    {
        $this->service->excluir($id);

        return response()->noContent();
    }
}
