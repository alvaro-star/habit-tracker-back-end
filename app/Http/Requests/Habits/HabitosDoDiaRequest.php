<?php

namespace App\Http\Requests\Habits;

use App\Rules\DataValida;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class HabitosDoDiaRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            /** Dia consultado, no formato AAAA-MM-DD. Sem ele, vale o dia de hoje. Um dia que o mês não tem (ex.: 2026-04-31) devolve 422. */
            'data' => ['sometimes', 'string', new DataValida],
        ];
    }

    public function dataConsultada(): CarbonImmutable
    {
        $data = $this->validated('data');

        return $data === null ? CarbonImmutable::today() : DataValida::converter($data);
    }
}
