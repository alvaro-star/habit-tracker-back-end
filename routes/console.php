<?php

use App\Services\NotificacaoService;
use App\Services\XpDiarioService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tarefas periódicas: eventos do scheduler que chamam os métodos dos services
// direto, sem jobs nem fila. Quem dispara é o serviço `scheduler` do
// docker-compose (`php artisan schedule:work`), ou um cron com `schedule:run`.

// Roda várias vezes ao dia porque cada usuário pode escolher o horário do aviso;
// o NotificacaoService garante um único envio por dia.
Schedule::call(fn (NotificacaoService $notificacoes) => $notificacoes->enviarAvisosDeStreak())
    ->everyFifteenMinutes()
    ->name('notificacoes:streak-em-risco')
    ->withoutOverlapping();

Schedule::call(fn (NotificacaoService $notificacoes) => $notificacoes->enviarReengajamento())
    ->hourly()
    ->name('notificacoes:reengajamento')
    ->withoutOverlapping();

// Pool semanal (ESCOPO.md, seção 3.3): ainda não implementado; o método está vazio.
Schedule::call(fn (XpDiarioService $xpDiario) => $xpDiario->fecharPoolSemanal())
    ->weeklyOn(1, '00:05')
    ->name('xp:pool-semanal')
    ->withoutOverlapping();
