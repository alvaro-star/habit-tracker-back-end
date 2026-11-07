<?php

namespace App\Http\Requests\Dragon;

use App\DTOs\Dragon\ElementoDTO;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class ElementoRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            'nome' => [$obrigatorio, 'string', 'max:20', Rule::unique('elemento', 'nome')->ignore($this->route('id'))],
            'cenario_url' => [$obrigatorio, 'string', 'max:255'],
        ];
    }

    public function toDto(): ElementoDTO
    {
        return ElementoDTO::fromArray($this->validated());
    }
}
