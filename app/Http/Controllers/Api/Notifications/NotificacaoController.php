<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Resources\Notifications\NotificacaoRecebidaResource;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificacaoController extends Controller
{
    public function __construct(protected NotificacaoService $service) {}

    /** Lista as 50 notificações mais recentes recebidas pelo usuário. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return NotificacaoRecebidaResource::collection($this->service->recebidas($request->user()));
    }
}
