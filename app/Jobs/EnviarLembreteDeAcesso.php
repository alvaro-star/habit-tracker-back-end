<?php

namespace App\Jobs;

use App\Services\NotificacaoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Lembrete diário de entrar no app, despachado pelo cron para quem ainda não acessou. */
class EnviarLembreteDeAcesso implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $usuarioId,
        public string $data,
    ) {}

    public function handle(NotificacaoService $notificacoes): void
    {
        $notificacoes->enviarLembreteDeAcesso($this->usuarioId, $this->data);
    }
}
