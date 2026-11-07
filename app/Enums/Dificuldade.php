<?php

namespace App\Enums;

enum Dificuldade: string
{
    case FACIL = 'FACIL';
    case MEDIO = 'MEDIO';
    case DIFICIL = 'DIFICIL';
    case MUITO_DIFICIL = 'MUITO_DIFICIL';

    /** Peso do hábito no cálculo de XP (ESCOPO.md, seção 3.2). */
    public function peso(): int
    {
        return match ($this) {
            self::FACIL => 5,
            self::MEDIO => 10,
            self::DIFICIL => 15,
            self::MUITO_DIFICIL => 20,
        };
    }
}
