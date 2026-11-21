<?php

namespace App\Notifications;

use App\Enums\CanalNotificacao;
use App\Models\GatilhoNotificacao;
use App\Models\Habito;
use App\Models\User;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as NotificacaoFcm;

/** Notificação de um gatilho do catálogo: grava em notifications e, se possível, dispara o push. */
class GatilhoNotification extends Notification
{
    /** O hábito só existe nos lembretes e preenche o {habito} do texto do gatilho. */
    public function __construct(public GatilhoNotificacao $gatilho, public ?Habito $habito = null) {}

    private function texto(): string
    {
        return str_replace('{habito}', $this->habito->nome ?? '', $this->gatilho->texto_base);
    }

    /**
     * O banco vem primeiro para o histórico não depender da entrega do push.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $podeEnviarPush = $this->gatilho->canal === CanalNotificacao::PUSH
            && config('notifications.push_ativo')
            && $notifiable instanceof User
            && filled($notifiable->token_dispositivo);

        return $podeEnviarPush ? ['database', FcmChannel::class] : ['database'];
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
            'texto' => $this->texto(),
            ...($this->habito !== null ? ['fk_id_habito' => $this->habito->id] : []),
        ];
    }

    /** O FCM só aceita textos nos valores do bloco data. */
    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            notification: new NotificacaoFcm(
                title: $this->habito->nome ?? config('notifications.titulo_push'),
                body: $this->texto(),
            ),
            data: [
                'tipo' => (string) $this->gatilho->tipo,
                'fk_id_gatilho' => (string) $this->gatilho->id,
                ...($this->habito !== null ? ['fk_id_habito' => (string) $this->habito->id] : []),
            ],
        );
    }
}
