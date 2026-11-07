<?php

namespace App\Http\Requests\Dragon;

use App\DTOs\Dragon\OvoDTO;
use App\Http\Requests\ApiRequest;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class OvoRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            'nome' => [$obrigatorio, 'string', 'max:40'],
            'imagem_url' => [$obrigatorio, 'string', 'max:255'],
        ];
    }

    public function toDto(): OvoDTO
    {
        return OvoDTO::fromArray($this->validated());
    }
}
