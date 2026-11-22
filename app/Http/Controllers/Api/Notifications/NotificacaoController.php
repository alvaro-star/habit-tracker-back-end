<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\NotificacaoManualRequest;
use App\Http\Resources\Notifications\NotificacaoAgendadaResource;
use App\Http\Resources\Notifications\NotificacaoRecebidaResource;
use App\Services\NotificacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NotificacaoController extends Controller
{
    public function __construct(protected NotificacaoService $service) {}

    /** Lista as 50 notificações mais recentes recebidas pelo usuário. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return NotificacaoRecebidaResource::collection($this->service->recebidas($request->user()));
    }

    /**
     * Agenda uma notificação para um usuário ou para todos.
     *
     * Um job por destinatário envia a notificação por FCM no dia e no horário
     * escolhidos, no fuso de Campo Grande, sem depender do cron. Com
     * `fk_id_usuario: null`, ela vai para todos os usuários cadastrados. Também
     * fica no histórico de quem recebe. Um dia ou horário no passado devolve 422.
     */
    public function store(NotificacaoManualRequest $request): JsonResponse
    {
        $dados = $request->toDto();
        $agendamento = $this->service->agendarNotificacaoManual($request->user()->id, $dados);

        return (new NotificacaoAgendadaResource($dados, $agendamento['enviar_em'], $agendamento['destinatarios']))
            ->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
