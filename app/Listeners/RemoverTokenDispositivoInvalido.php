<?php

namespace App\Listeners;

use App\Repositories\UsuarioRepository;
use Illuminate\Notifications\Events\NotificationFailed;
use Kreait\Firebase\Messaging\SendReport;
use NotificationChannels\Fcm\FcmChannel;

/** Apaga o token que o FCM informou como não registrado (ex.: app desinstalado). */
class RemoverTokenDispositivoInvalido
{
    public function __construct(protected UsuarioRepository $usuarios) {}

    public function handle(NotificationFailed $evento): void
    {
        $relatorio = $evento->data['report'] ?? null;

        if ($evento->channel === FcmChannel::class
            && $relatorio instanceof SendReport
            && $relatorio->messageWasSentToUnknownToken()) {
            $this->usuarios->removerTokenDispositivo($relatorio->target()->value());
        }
    }
}
