<?php

namespace App\Notifications;

use App\Enums\CanalNotificacao;
use App\Models\User;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as NotificacaoFcm;

/** Notificação escrita por um usuário: grava em notifications e, se possível, dispara o push. */
class NotificacaoManual extends Notification
{
    public const TIPO = 'MANUAL';

    public function __construct(
        public int $remetenteId,
        public string $titulo,
        public string $texto,
    ) {}

    /**
     * O banco vem primeiro para o histórico não depender da entrega do push.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $podeEnviarPush = config('notifications.push_ativo')
            && $notifiable instanceof User
            && filled($notifiable->token_dispositivo);

        return $podeEnviarPush ? ['database', FcmChannel::class] : ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return self::TIPO;
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'canal' => CanalNotificacao::PUSH->value,
            'titulo' => $this->titulo,
            'texto' => $this->texto,
            'fk_id_remetente' => $this->remetenteId,
        ];
    }

    /** O FCM só aceita textos nos valores do bloco data. */
    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            notification: new NotificacaoFcm(title: $this->titulo, body: $this->texto),
            data: [
                'tipo' => self::TIPO,
                'fk_id_remetente' => (string) $this->remetenteId,
            ],
        );
    }
}
