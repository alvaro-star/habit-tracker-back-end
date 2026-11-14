<?php

namespace App\Http\Controllers\Api\Dragon;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dragon\SantuarioResource;
use App\Services\DragaoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SantuarioController extends Controller
{
    public function __construct(protected DragaoService $service) {}

    /**
     * Santuário do usuário.
     *
     * Lista os dragões que concluíram o ciclo (status `SANTUARIO`), do término
     * mais recente para o mais antigo. Cada entrada traz o nome, a arte final, o
     * período de vida, a trajetória de elementos e o tempo total do ciclo.
     * Um dragão entra no Santuário quando o seu status passa a `SANTUARIO`
     * (`/api/dragon/dragons`) e não pode mais ser excluído.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return SantuarioResource::collection($this->service->santuario($request->user()->id));
    }

    /**
     * Uma entrada do Santuário.
     *
     * Um dragão ativo ou de outro usuário devolve 404.
     */
    public function show(Request $request, int $id): SantuarioResource
    {
        return new SantuarioResource($this->service->buscarNoSantuario($request->user()->id, $id));
    }
}
