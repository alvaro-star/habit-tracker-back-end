<?php

namespace Database\Seeders;

use App\Enums\CanalNotificacao;
use App\Enums\TipoGatilho;
use App\Models\GatilhoNotificacao;
use Illuminate\Database\Seeder;

/** Catálogo da seção 7.2 do escopo. Pode rodar mais de uma vez. */
class GatilhoNotificacaoSeeder extends Seeder
{
    public function run(): void
    {
        $gatilhos = [
            [TipoGatilho::LEMBRETE_DIARIO, 'Horário agendado', 'Hora de {habito}! Seu dragão está contando com você.'],
            [TipoGatilho::STREAK_EM_RISCO, 'A cada 4 horas', 'Você ainda não cumpriu todos os hábitos de hoje. Complete-os para ganhar todo o XP do dia!'],
            [TipoGatilho::INATIVIDADE_3_DIAS, 'Evento', 'Seu dragão está com saudades! Volte e continue sua jornada.'],
            [TipoGatilho::INATIVIDADE_7_DIAS, 'Evento', 'Sua jornada está pausada. Que tal voltar com um hábito fácil hoje?'],
        ];

        foreach ($gatilhos as [$tipo, $frequencia, $texto]) {
            GatilhoNotificacao::updateOrCreate(
                ['tipo' => $tipo->value],
                ['canal' => CanalNotificacao::PUSH, 'frequencia' => $frequencia, 'texto_base' => $texto],
            );
        }
    }
}
