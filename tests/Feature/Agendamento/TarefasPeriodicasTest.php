<?php

namespace Tests\Feature\Agendamento;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class TarefasPeriodicasTest extends TestCase
{
    /** @return array<string, CallbackEvent> */
    private function eventos(): array
    {
        $eventos = [];

        foreach ($this->app->make(Schedule::class)->events() as $evento) {
            $eventos[$evento->description] = $evento;
        }

        return $eventos;
    }

    public function test_tarefas_periodicas_sao_eventos_do_scheduler_sem_jobs(): void
    {
        $eventos = $this->eventos();

        $this->assertSame('*/15 * * * *', $eventos['notificacoes:streak-em-risco']->expression);
        $this->assertSame('0 * * * *', $eventos['notificacoes:reengajamento']->expression);
        $this->assertSame('*/30 * * * *', $eventos['notificacoes:lembretes-habitos']->expression);
        $this->assertSame('5 0 * * 1', $eventos['xp:pool-semanal']->expression);

        foreach ($eventos as $evento) {
            $this->assertInstanceOf(CallbackEvent::class, $evento);
        }

        $this->assertDirectoryDoesNotExist(app_path('Jobs'));
    }
}
