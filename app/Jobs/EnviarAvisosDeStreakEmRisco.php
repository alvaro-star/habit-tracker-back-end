<?php

namespace App\Jobs;

use App\Services\NotificacaoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnviarAvisosDeStreakEmRisco implements ShouldQueue
{
    use Queueable;

    public function handle(NotificacaoService $notificacoes): void
    {
        $notificacoes->enviarAvisosDeStreak();
    }
}
