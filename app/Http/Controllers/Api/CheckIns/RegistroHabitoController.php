<?php

namespace App\Http\Controllers\Api\CheckIns;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckIns\RegistroHabitoRequest;
use App\Http\Resources\CheckIns\RegistroHabitoResource;
use App\Services\RegistroHabitoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RegistroHabitoController extends Controller
{
    public function __construct(protected RegistroHabitoService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return RegistroHabitoResource::collection($this->service->listarDoUsuario($request->user()->id));
    }

    /** A posse do hábito informado é validada na RegistroHabitoRequest. */
    public function store(RegistroHabitoRequest $request): JsonResponse
    {
        return (new RegistroHabitoResource($this->service->criar($request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id): RegistroHabitoResource
    {
        return new RegistroHabitoResource($this->service->buscarDoUsuario($request->user()->id, $id));
    }

    public function update(RegistroHabitoRequest $request, int $id): RegistroHabitoResource
    {
        return new RegistroHabitoResource($this->service->atualizarDoUsuario($request->user()->id, $id, $request->toDto()));
    }

    public function destroy(Request $request, int $id): Response
    {
        $this->service->excluirDoUsuario($request->user()->id, $id);

        return response()->noContent();
    }
}
