<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\RedefinirSenhaDTO;
use App\Http\Requests\ApiRequest;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class RedefinirSenhaRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function toDto(): RedefinirSenhaDTO
    {
        return RedefinirSenhaDTO::fromArray($this->validated());
    }
}
