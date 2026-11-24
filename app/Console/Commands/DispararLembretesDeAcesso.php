<?php

namespace App\Console\Commands;

use App\Services\NotificacaoService;
use Illuminate\Console\Command;

/** Chamado pelo cron: despacha o job do lembrete para quem ainda não acessou hoje. */
class DispararLembretesDeAcesso extends Command
{
    protected $signature = 'notificacoes:lembrete-acesso';

    protected $description = 'Despacha o lembrete diário de entrar no app para quem ainda não acessou hoje';

    public function handle(NotificacaoService $notificacoes): int
    {
        $despachados = $notificacoes->agendarLembretesDeAcesso();

        $this->info("Lembretes de acesso despachados: {$despachados}");

        return self::SUCCESS;
    }
}
