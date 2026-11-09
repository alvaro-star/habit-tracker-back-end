<?php

use App\Jobs\EnviarAvisosDeStreakEmRisco;
use App\Jobs\EnviarNotificacoesDeReengajamento;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Roda várias vezes ao dia porque cada usuário pode escolher o horário do aviso;
// o NotificacaoService garante um único envio por dia.
Schedule::job(new EnviarAvisosDeStreakEmRisco)->everyFifteenMinutes();
Schedule::job(new EnviarNotificacoesDeReengajamento)->hourly();
