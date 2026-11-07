<?php

namespace App\Enums;

enum Frequencia: string
{
    case DIARIA = 'DIARIA';
    case SEMANAL = 'SEMANAL';
    case MENSAL = 'MENSAL';

    /**
     * Confere o dia_frequencia de um hábito com esta frequência: o diário não
     * tem dia (null), o semanal usa um DiaSemana e o mensal, um dia de 1 a 31.
     */
    public function aceitaDia(mixed $dia): bool
    {
        if ($dia instanceof DiaSemana) {
            $dia = $dia->value;
        }

        return match ($this) {
            self::DIARIA => $dia === null,
            self::SEMANAL => is_string($dia) && DiaSemana::tryFrom($dia) !== null,
            self::MENSAL => (is_int($dia) || is_string($dia))
                && filter_var($dia, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 31]]) !== false,
        };
    }

    /** Mensagem de erro de um dia_frequencia que não combina com esta frequência. */
    public function mensagemDiaInvalido(): string
    {
        $regra = match ($this) {
            self::DIARIA => 'deve ser nulo',
            self::SEMANAL => 'deve ser um dia da semana ('.implode(', ', array_column(DiaSemana::cases(), 'value')).')',
            self::MENSAL => 'deve ser um dia do mês, de 1 a 31',
        };

        return "O dia_frequencia é inválido: na frequência {$this->value}, ele {$regra}.";
    }
}
