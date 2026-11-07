<?php

namespace App\Http\Controllers\Api\Progression;

use App\Http\Controllers\Controller;
use App\Http\Requests\Progression\XpDiarioIntervaloRequest;
use App\Http\Resources\Progression\XpDiarioResource;
use App\Services\XpDiarioService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class XpDiarioController extends Controller
{
    public function __construct(protected XpDiarioService $service) {}

    /**
     * XP diário em um intervalo.
     *
     * Busca no banco o XP registrado entre `data_inicio` e `data_fim`, do dia mais
     * recente para o mais antigo, inclusive o de hoje. O registro de cada dia é
     * atualizado pelo sistema sempre que um check-in é marcado ou desmarcado, ou um
     * hábito é criado ou desativado. O XP diário não é criado nem editado pelo usuário.
     */
    public function index(XpDiarioIntervaloRequest $request): AnonymousResourceCollection
    {
        return XpDiarioResource::collection(
            $this->service->historicoDoUsuario($request->user(), $request->dataInicio(), $request->dataFim()),
        );
    }
}
