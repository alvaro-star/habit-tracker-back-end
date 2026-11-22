<?php

namespace App\Jobs;

use App\Services\NotificacaoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Notificação criada por um usuário, agendada para o dia e o horário escolhidos. */
class EnviarNotificacaoManual implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $destinatarioId,
        public int $remetenteId,
        public string $titulo,
        public string $texto,
    ) {}

    public function handle(NotificacaoService $notificacoes): void
    {
        $notificacoes->enviarNotificacaoManual($this->destinatarioId, $this->remetenteId, $this->titulo, $this->texto);
    }
}
