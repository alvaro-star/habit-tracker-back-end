<?php

namespace App\Enums;

enum Dificuldade: string
{
    case FACIL = 'FACIL';
    case MEDIO = 'MEDIO';
    case DIFICIL = 'DIFICIL';
    case MUITO_DIFICIL = 'MUITO_DIFICIL';

    /** Limites do peso que o usuário pode dar a um hábito. */
    public const PESO_MINIMO = 1;

    public const PESO_MAXIMO = 20;

    /**
     * Dificuldade definida pelo peso do hábito, em faixas que terminam nos pesos
     * de referência do ESCOPO.md (seção 3.2): 1–5 Fácil, 6–10 Médio,
     * 11–15 Difícil e 16–20 Muito Difícil.
     */
    public static function doPeso(int $peso): self
    {
        return match (true) {
            $peso <= 5 => self::FACIL,
            $peso <= 10 => self::MEDIO,
            $peso <= 15 => self::DIFICIL,
            default => self::MUITO_DIFICIL,
        };
    }

    /** Peso de referência da dificuldade (ESCOPO.md, seção 3.2): o maior peso da faixa. */
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
