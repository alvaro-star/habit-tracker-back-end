<?php

namespace App\Http\Requests\CheckIns;

use App\DTOs\CheckIns\RegistroHabitoDTO;
use App\Enums\StatusRegistro;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class RegistroHabitoRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            // Só na criação: o hábito precisa pertencer ao usuário e não muda depois.
            'fk_id_habito' => $this->isMethod('POST')
                ? ['required', 'integer', Rule::exists('habito', 'id')->where('fk_id_usuario', $this->user()?->id)]
                : ['exclude'],
            'data' => [$obrigatorio, 'date_format:Y-m-d'],
            'status' => [$obrigatorio, Rule::enum(StatusRegistro::class)],
            'valor_realizado' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'percentual' => ['sometimes', 'numeric', 'between:0,100'],
        ];
    }

    public function toDto(): RegistroHabitoDTO
    {
        return RegistroHabitoDTO::fromArray($this->validated());
    }
}
