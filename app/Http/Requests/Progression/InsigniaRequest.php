<?php

namespace App\Http\Requests\Progression;

use App\DTOs\Progression\InsigniaDTO;
use App\Http\Requests\ApiRequest;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class InsigniaRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            'nome' => [$obrigatorio, 'string', 'max:60'],
            'descricao' => [$obrigatorio, 'string', 'max:255'],
            'icone_url' => [$obrigatorio, 'string', 'max:255'],
        ];
    }

    public function toDto(): InsigniaDTO
    {
        return InsigniaDTO::fromArray($this->validated());
    }
}
