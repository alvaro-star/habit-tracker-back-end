<?php

namespace App\Rules;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/** Data no formato AAAA-MM-DD que existe no calendário (2026-04-31 é recusada). */
class DataValida implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::converter($value) === null) {
            $fail("O campo {$attribute} é uma data inválida: use o formato AAAA-MM-DD com um dia que exista no mês.");
        }
    }

    /**
     * O Carbon "corrige" um dia inexistente (2026-04-31 vira 2026-05-01); por
     * isso a data só vale se, formatada de volta, continuar igual à enviada.
     */
    public static function converter(mixed $valor): ?CarbonImmutable
    {
        if (! is_string($valor)) {
            return null;
        }

        try {
            $data = CarbonImmutable::createFromFormat('!Y-m-d', $valor);
        } catch (Throwable) {
            return null;
        }

        return $data !== null && $data->format('Y-m-d') === $valor ? $data : null;
    }
}
