<?php

namespace App\Http\Controllers\Api\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Library\HabitoModeloRequest;
use App\Http\Resources\Library\HabitoModeloResource;
use App\Services\HabitoModeloService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class HabitoModeloController extends Controller
{
    public function __construct(protected HabitoModeloService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return HabitoModeloResource::collection($this->service->listar());
    }

    public function store(HabitoModeloRequest $request): JsonResponse
    {
        return (new HabitoModeloResource($this->service->criar($request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id): HabitoModeloResource
    {
        return new HabitoModeloResource($this->service->buscar($id));
    }

    public function update(HabitoModeloRequest $request, int $id): HabitoModeloResource
    {
        return new HabitoModeloResource($this->service->atualizar($id, $request->toDto()));
    }

    public function destroy(int $id): Response
    {
        $this->service->excluir($id);

        return response()->noContent();
    }
}
