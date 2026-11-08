<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/** Dia da semana de um hábito semanal (coluna habito.dia_frequencia). */
enum DiaSemana: string
{
    case SEG = 'SEG';
    case TER = 'TER';
    case QUA = 'QUA';
    case QUI = 'QUI';
    case SEX = 'SEX';
    case SAB = 'SAB';
    case DOM = 'DOM';

    public static function daData(CarbonInterface $data): self
    {
        // Os casos estão declarados de SEG a DOM, a mesma ordem do dia ISO (1 a 7).
        return self::cases()[$data->dayOfWeekIso - 1];
    }
}
