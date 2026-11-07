<?php

namespace App\Http\Requests\Dragon;

use App\DTOs\Dragon\EstagioDTO;
use App\Http\Requests\ApiRequest;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class EstagioRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            'nome' => [$obrigatorio, 'string', 'max:30'],
            'ordem' => [$obrigatorio, 'integer', 'min:0'],
            'xp_marco' => [$obrigatorio, 'integer', 'min:0'],
        ];
    }

    public function toDto(): EstagioDTO
    {
        return EstagioDTO::fromArray($this->validated());
    }
}
