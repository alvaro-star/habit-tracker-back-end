<?php

namespace App\Http\Controllers\Api\Progression;

use App\Http\Controllers\Controller;
use App\Http\Requests\Progression\InsigniaRequest;
use App\Http\Resources\Progression\InsigniaResource;
use App\Services\InsigniaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class InsigniaController extends Controller
{
    public function __construct(protected InsigniaService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return InsigniaResource::collection($this->service->listar());
    }

    public function store(InsigniaRequest $request): JsonResponse
    {
        return (new InsigniaResource($this->service->criar($request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id): InsigniaResource
    {
        return new InsigniaResource($this->service->buscar($id));
    }

    public function update(InsigniaRequest $request, int $id): InsigniaResource
    {
        return new InsigniaResource($this->service->atualizar($id, $request->toDto()));
    }

    public function destroy(int $id): Response
    {
        $this->service->excluir($id);

        return response()->noContent();
    }
}
