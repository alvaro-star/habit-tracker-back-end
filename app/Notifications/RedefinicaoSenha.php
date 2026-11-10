<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificação de redefinição de senha para API.
 *
 * Como o projeto é somente API, não há rota web de redefinição.
 * O e-mail inclui apenas o token, que o front-end deve capturar
 * e enviar para `POST /api/auth/reset-password`.
 */
class RedefinicaoSenha extends Notification
{
    public function __construct(
        private readonly string $token,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Redefinição de senha — Habit Tracker')
            ->greeting('Olá!')
            ->line('Você solicitou a redefinição da sua senha no Habit Tracker.')
            ->line('Seu token de redefinição é:')
            ->line("**{$this->token}**")
            ->line('Envie este token para o endpoint `POST /api/auth/reset-password` junto com o e-mail e a nova senha.')
            ->line('O token expira em 60 minutos.')
            ->salutation('Equipe Habit Tracker');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'token' => $this->token,
        ];
    }
}
