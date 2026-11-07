<?php

namespace App\Http\Controllers\Api\Habits;

use App\Http\Controllers\Controller;
use App\Http\Requests\Habits\HabitoRequest;
use App\Http\Requests\Habits\HabitosDoDiaRequest;
use App\Http\Resources\Habits\HabitoResource;
use App\Services\HabitoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class HabitoController extends Controller
{
    public function __construct(protected HabitoService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return HabitoResource::collection($this->service->listarDoUsuario($request->user()->id));
    }

    /**
     * Hábitos do dia.
     *
     * Lista os hábitos ativos que devem ser cumpridos na data: os diários, os
     * semanais daquele dia da semana e os mensais daquele dia do mês.
     */
    public function daily(HabitosDoDiaRequest $request): AnonymousResourceCollection
    {
        return HabitoResource::collection(
            $this->service->habitosDiarios($request->user()->id, $request->dataConsultada()),
        );
    }

    /**
     * Cria um hábito.
     *
     * A `frequencia` e o `dia_frequencia` são sempre enviados juntos: `null` no `DIARIA`,
     * `SEG` a `DOM` no `SEMANAL` e um dia de 1 a 31 no `MENSAL`. Um hábito mensal dos dias
     * 29, 30 ou 31 só aparece nos meses que têm esse dia; nos demais meses ele não é listado.
     */
    public function store(HabitoRequest $request): JsonResponse
    {
        return (new HabitoResource($this->service->criarParaUsuario($request->user()->id, $request->toDto())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id): HabitoResource
    {
        return new HabitoResource($this->service->buscarDoUsuario($request->user()->id, $id));
    }

    public function update(HabitoRequest $request, int $id): HabitoResource
    {
        return new HabitoResource($this->service->atualizarDoUsuario($request->user()->id, $id, $request->toDto()));
    }

    /**
     * Desativa um hábito.
     *
     * O hábito não é apagado: ele passa a `ativo = false` e mantém os seus check-ins,
     * para o histórico e as estatísticas. Para reativá-lo, envie `ativo: true` no PUT/PATCH.
     * Um hábito inativo continua na listagem e no `GET /api/habits/{id}`, mas sai dos hábitos do dia.
     */
    public function destroy(Request $request, int $id): Response
    {
        $this->service->excluirDoUsuario($request->user()->id, $id);

        return response()->noContent();
    }
}
