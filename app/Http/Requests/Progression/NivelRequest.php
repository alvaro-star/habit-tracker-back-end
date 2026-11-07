<?php

namespace App\Http\Requests\Progression;

use App\DTOs\Progression\NivelDTO;
use App\Http\Requests\ApiRequest;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class NivelRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            'xp_necessario' => [$obrigatorio, 'integer', 'min:0'],
            'xp_acumulado' => [$obrigatorio, 'integer', 'min:0'],
        ];
    }

    public function toDto(): NivelDTO
    {
        return NivelDTO::fromArray($this->validated());
    }
}
