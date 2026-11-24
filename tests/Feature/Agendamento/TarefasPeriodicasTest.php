<?php

namespace Tests\Feature\Agendamento;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class TarefasPeriodicasTest extends TestCase
{
    /** @return array<string, Event> */
    private function eventos(): array
    {
        $eventos = [];

        foreach ($this->app->make(Schedule::class)->events() as $evento) {
            $eventos[$evento->description] = $evento;
        }

        return $eventos;
    }

    public function test_tarefas_periodicas_estao_na_agenda(): void
    {
        $eventos = $this->eventos();

        $this->assertSame('0 */4 * * *', $eventos['notificacoes:streak-em-risco']->expression);
        $this->assertSame('0 0 * * *', $eventos['notificacoes:reengajamento']->expression);
        $this->assertSame('*/30 * * * *', $eventos['notificacoes:lembretes-habitos']->expression);
        $this->assertSame('*/30 * * * *', $eventos['notificacoes:lembrete-acesso']->expression);
        $this->assertSame('5 0 * * 1', $eventos['xp:pool-semanal']->expression);
        $this->assertSame('*/5 * * * *', $eventos['horizon:snapshot']->expression);

        foreach (['notificacoes:streak-em-risco', 'notificacoes:reengajamento', 'notificacoes:lembretes-habitos', 'xp:pool-semanal'] as $nome) {
            $this->assertInstanceOf(CallbackEvent::class, $eventos[$nome]);
        }

        $this->assertNotInstanceOf(CallbackEvent::class, $eventos['notificacoes:lembrete-acesso']);
        $this->assertStringContainsString('notificacoes:lembrete-acesso', (string) $eventos['notificacoes:lembrete-acesso']->command);
    }
}
