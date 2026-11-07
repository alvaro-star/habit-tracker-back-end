<?php

namespace App\Http\Controllers\Api\Progression;

use App\Http\Controllers\Controller;
use App\Http\Requests\Progression\XpDiarioRequest;
use App\Http\Resources\Progression\XpDiarioResource;
use App\Services\XpDiarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class XpDiarioController extends Controller
{
    public function __construct(protected XpDiarioService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return XpDiarioResource::collection($this->service->listarDoUsuario($request->user()->id));
    }

    public function store(XpDiarioRequest $request): JsonResponse
    {
        return (new XpDiarioResource($this->service->criarParaUsuario($request->user()->id, $request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id): XpDiarioResource
    {
        return new XpDiarioResource($this->service->buscarDoUsuario($request->user()->id, $id));
    }

    public function update(XpDiarioRequest $request, int $id): XpDiarioResource
    {
        return new XpDiarioResource($this->service->atualizarDoUsuario($request->user()->id, $id, $request->toDto()));
    }

    public function destroy(Request $request, int $id): Response
    {
        $this->service->excluirDoUsuario($request->user()->id, $id);

        return response()->noContent();
    }
}
