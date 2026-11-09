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
            [TipoGatilho::STREAK_EM_RISCO, 'Diário (noite)', 'Seu dragão sente sua falta! Complete um hábito hoje para manter sua sequência.'],
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
