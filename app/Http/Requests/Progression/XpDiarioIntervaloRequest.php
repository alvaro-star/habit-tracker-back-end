<?php

namespace App\Http\Requests\Progression;

use App\Rules\DataValida;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class XpDiarioIntervaloRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            /** Primeiro dia do intervalo, no formato AAAA-MM-DD. */
            'data_inicio' => ['required', 'string', new DataValida],
            /** Último dia do intervalo, no formato AAAA-MM-DD; não pode ser anterior à data_inicio. */
            'data_fim' => ['required', 'string', new DataValida, 'after_or_equal:data_inicio'],
        ];
    }

    public function dataInicio(): CarbonImmutable
    {
        return DataValida::converter($this->validated('data_inicio'));
    }

    public function dataFim(): CarbonImmutable
    {
        return DataValida::converter($this->validated('data_fim'));
    }
}
