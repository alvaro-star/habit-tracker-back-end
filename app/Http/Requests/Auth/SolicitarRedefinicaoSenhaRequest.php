<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\SolicitarRedefinicaoSenhaDTO;
use App\Http\Requests\ApiRequest;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class SolicitarRedefinicaoSenhaRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function toDto(): SolicitarRedefinicaoSenhaDTO
    {
        return SolicitarRedefinicaoSenhaDTO::fromArray($this->validated());
    }
}
