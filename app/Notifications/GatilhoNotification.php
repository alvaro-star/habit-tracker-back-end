<?php

namespace App\Notifications;

use App\Models\GatilhoNotificacao;
use Illuminate\Notifications\Notification;

/** Notificação gravada na tabela notifications a partir de um gatilho do catálogo. */
class GatilhoNotification extends Notification
{
    public function __construct(public GatilhoNotificacao $gatilho) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** Grava o tipo do gatilho na coluna type, que é usada para não repetir o envio. */
    public function databaseType(object $notifiable): string
    {
        return $this->gatilho->tipo;
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'fk_id_gatilho' => $this->gatilho->id,
            'canal' => $this->gatilho->canal->value,
            'texto' => $this->gatilho->texto_base,
        ];
    }
}
