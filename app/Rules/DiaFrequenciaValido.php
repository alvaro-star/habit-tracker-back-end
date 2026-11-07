<?php

namespace App\Rules;

use App\Enums\Frequencia;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida o dia_frequencia contra a frequencia enviada na mesma requisição.
 * Os dois campos andam juntos: enviar a frequencia exige o dia_frequencia
 * (null no DIARIA), e enviar o dia_frequencia exige a frequencia (essa
 * exigência fica na regra da própria frequencia, na HabitoRequest).
 */
class DiaFrequenciaValido implements DataAwareRule, ValidationRule
{
    /** Roda mesmo com o campo ausente ou nulo, para exigir o dia no semanal e no mensal. */
    public bool $implicit = true;

    /** @var array<string, mixed> */
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (array_key_exists('frequencia', $this->data) && ! array_key_exists('dia_frequencia', $this->data)) {
            $fail('O dia_frequencia deve ser enviado junto com a frequencia (use null na frequência DIARIA).');

            return;
        }

        $frequencia = Frequencia::tryFrom((string) ($this->data['frequencia'] ?? ''));

        if ($frequencia !== null) {
            if (! $frequencia->aceitaDia($value)) {
                $fail($frequencia->mensagemDiaInvalido());
            }

            return;
        }

        if ($value !== null && ! Frequencia::SEMANAL->aceitaDia($value) && ! Frequencia::MENSAL->aceitaDia($value)) {
            $fail('O dia_frequencia é inválido: use um dia da semana (SEG a DOM) ou um dia do mês (1 a 31).');
        }
    }
}
