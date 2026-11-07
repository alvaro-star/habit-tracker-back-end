<?php

namespace App\Http\Requests\Habits;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

class HabitosDoDiaRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            /** Dia consultado, no formato AAAA-MM-DD. Sem ele, vale o dia de hoje. Um dia que o mês não tem (ex.: 2026-04-31) devolve 422. */
            'data' => ['sometimes', 'string', function (string $atributo, mixed $valor, Closure $fail) {
                if (self::converterData($valor) === null) {
                    $fail('A data é inválida: use o formato AAAA-MM-DD com um dia que exista no mês.');
                }
            }],
        ];
    }

    public function dataConsultada(): CarbonImmutable
    {
        $data = $this->validated('data');

        return $data === null ? CarbonImmutable::today() : self::converterData($data);
    }

    /**
     * O Carbon "corrige" um dia inexistente (2026-04-31 vira 2026-05-01); por
     * isso a data só vale se, formatada de volta, continuar igual à enviada.
     */
    private static function converterData(mixed $valor): ?CarbonImmutable
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
