<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\DeletarContaDTO;
use App\Http\Requests\ApiRequest;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class DeletarContaRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
        ];
    }

    public function toDto(): DeletarContaDTO
    {
        return DeletarContaDTO::fromArray($this->validated());
    }
}
