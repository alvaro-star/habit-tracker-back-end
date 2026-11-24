<?php

use App\Services\NotificacaoService;
use App\Services\XpDiarioService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tarefas periódicas: eventos do scheduler que chamam os métodos dos services.
// Quem dispara é o serviço `scheduler` do
// docker-compose (`php artisan schedule:work`), ou um cron com `schedule:run`.

// A cada 4 horas, avisa quem tem sequência ativa e ainda não fez todo o XP do dia.
Schedule::call(fn (NotificacaoService $notificacoes) => $notificacoes->enviarAvisosDeStreak())
    ->everyFourHours()
    ->name('notificacoes:streak-em-risco')
    ->withoutOverlapping();

// Uma vez por dia, agenda um job para o momento em que cada usuário completa
// 3 ou 7 dias sem acesso.
Schedule::call(fn (NotificacaoService $notificacoes) => $notificacoes->agendarAvisosDeInatividade())
    ->daily()
    ->name('notificacoes:reengajamento')
    ->withoutOverlapping();

// A cada 30 minutos, agenda um job no horário exato de cada lembrete do bloco:
// o cron das 07:00 cobre de 07:00 a antes das 07:30, e assim por diante.
Schedule::call(fn (NotificacaoService $notificacoes) => $notificacoes->agendarLembretesDeHabitos())
    ->everyThirtyMinutes()
    ->name('notificacoes:lembretes-habitos')
    ->withoutOverlapping();

// A cada 30 minutos, lembra de entrar no app quem ainda não acessou hoje (um por dia).
Schedule::call(fn (NotificacaoService $notificacoes) => $notificacoes->agendarLembretesDeAcesso())
    ->everyThirtyMinutes()
    ->name('notificacoes:lembrete-acesso')
    ->withoutOverlapping();

// Métricas do painel do Horizon.
Schedule::command('horizon:snapshot')
    ->everyFiveMinutes()
    ->name('horizon:snapshot')
    ->withoutOverlapping();

// Pool semanal (ESCOPO.md, seção 3.3): ainda não implementado; o método está vazio.
Schedule::call(fn (XpDiarioService $xpDiario) => $xpDiario->fecharPoolSemanal())
    ->weeklyOn(1, '00:05')
    ->name('xp:pool-semanal')
    ->withoutOverlapping();
