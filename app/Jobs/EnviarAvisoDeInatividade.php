<?php

namespace App\Jobs;

use App\Enums\TipoGatilho;
use App\Services\NotificacaoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Aviso de 3 ou 7 dias sem acesso, agendado para o momento em que o prazo se completa. */
class EnviarAvisoDeInatividade implements ShouldQueue
{
    use Queueable;

    /** @param string $ultimoAcesso último acesso (Y-m-d H:i:s) usado no agendamento */
    public function __construct(
        public int $usuarioId,
        public TipoGatilho $tipo,
        public string $ultimoAcesso,
    ) {}

    public function handle(NotificacaoService $notificacoes): void
    {
        $notificacoes->enviarAvisoDeInatividade($this->usuarioId, $this->tipo, $this->ultimoAcesso);
    }
}
