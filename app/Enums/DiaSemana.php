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
        return match ($data->dayOfWeekIso) {
            1 => self::SEG,
            2 => self::TER,
            3 => self::QUA,
            4 => self::QUI,
            5 => self::SEX,
            6 => self::SAB,
            7 => self::DOM,
        };
    }
}
