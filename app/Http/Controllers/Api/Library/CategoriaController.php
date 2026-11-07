<?php

namespace App\Http\Controllers\Api\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Library\CategoriaRequest;
use App\Http\Resources\Library\CategoriaResource;
use App\Services\CategoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoriaController extends Controller
{
    public function __construct(protected CategoriaService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return CategoriaResource::collection($this->service->listar());
    }

    public function store(CategoriaRequest $request): JsonResponse
    {
        return (new CategoriaResource($this->service->criar($request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id): CategoriaResource
    {
        return new CategoriaResource($this->service->buscar($id));
    }

    public function update(CategoriaRequest $request, int $id): CategoriaResource
    {
        return new CategoriaResource($this->service->atualizar($id, $request->toDto()));
    }

    public function destroy(int $id): Response
    {
        $this->service->excluir($id);

        return response()->noContent();
    }
}
