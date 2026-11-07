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

    /**
     * Marca ou desmarca um hábito no dia.
     *
     * Com `checked: true`, cria o check-in do hábito no dia (201) ou atualiza o que já
     * existe para o mesmo `fk_id_habito` + `data` (200). Com `checked: false`, exclui o
     * check-in do dia (204). Não há PUT nem DELETE: o POST é a única forma de gravar.
     *
     * O status depende do tipo de meta do hábito. Em `REALIZACAO`, vale o `status` enviado
     * (sem ele, `NAO_CONCLUIDO`). Em `TEMPO` e `QUANTIDADE`, o `valor_realizado` é comparado
     * com o `valor_meta` do hábito: vazio ou zero → `NAO_CONCLUIDO`; atingiu a meta →
     * `CONCLUIDO`; parcial → `EM_ANDAMENTO`, com o `percentual` proporcional.
     *
     * A data não pode ser futura nem anterior ao nascimento do dragão ativo (422), e não
     * pode ser anterior à criação do hábito (409). Se a data é anterior a hoje, o XP daquele
     * dia é recalculado e a diferença é somada ao XP do dragão ativo e ao XP vitalício.
     */
    public function store(RegistroHabitoRequest $request): JsonResponse|Response
    {
        $registro = $this->service->registrar($request->user(), $request->toDto());

        if ($registro === null) {
            return response()->noContent();
        }

        return (new RegistroHabitoResource($registro))
            ->response()
            ->setStatusCode($registro->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    public function show(Request $request, int $id): RegistroHabitoResource
    {
        return new RegistroHabitoResource($this->service->buscarDoUsuario($request->user()->id, $id));
    }
}
