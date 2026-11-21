<?php

namespace App\Jobs;

use App\Services\NotificacaoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Lembrete de um hábito, agendado pelo cron para sair no horário exato. */
class EnviarLembreteDeHabito implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $habitoId,
        public int $usuarioId,
        public string $data,
        public string $horario,
    ) {}

    public function handle(NotificacaoService $notificacoes): void
    {
        $notificacoes->enviarLembreteDeHabito($this->habitoId, $this->usuarioId, $this->data, $this->horario);
    }
}
