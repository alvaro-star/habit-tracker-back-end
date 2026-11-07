<?php

namespace App\Http\Controllers\Api\Dragon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dragon\DragaoRequest;
use App\Http\Resources\Dragon\DragaoResource;
use App\Services\DragaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DragaoController extends Controller
{
    public function __construct(protected DragaoService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return DragaoResource::collection($this->service->listarDoUsuario($request->user()->id));
    }

    public function store(DragaoRequest $request): JsonResponse
    {
        return (new DragaoResource($this->service->criarParaUsuario($request->user()->id, $request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id): DragaoResource
    {
        return new DragaoResource($this->service->buscarDoUsuario($request->user()->id, $id));
    }

    public function update(DragaoRequest $request, int $id): DragaoResource
    {
        return new DragaoResource($this->service->atualizarDoUsuario($request->user()->id, $id, $request->toDto()));
    }

    public function destroy(Request $request, int $id): Response
    {
        $this->service->excluirDoUsuario($request->user()->id, $id);

        return response()->noContent();
    }
}
