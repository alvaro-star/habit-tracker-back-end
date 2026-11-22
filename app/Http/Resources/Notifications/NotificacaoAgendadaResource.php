<?php

namespace App\Http\Resources\Notifications;

use App\DTOs\Notifications\NotificacaoManualDTO;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotificacaoManualDTO */
class NotificacaoAgendadaResource extends JsonResource
{
    public function __construct(
        NotificacaoManualDTO $resource,
        private CarbonInterface $enviarEm,
        private int $destinatarios,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            // Nulo quando a notificação vai para todos os usuários.
            'fk_id_usuario' => $this->fk_id_usuario,
            'destinatarios' => $this->destinatarios,
            'titulo' => $this->titulo,
            'texto' => $this->texto,
            'enviar_em' => $this->enviarEm->toIso8601String(),
        ];
    }
}
