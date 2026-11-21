<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\LoginDTO;
use App\Http\Requests\ApiRequest;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class LoginRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'token_dispositivo' => ['nullable', 'string', 'max:512'],
        ];
    }

    public function toDto(): LoginDTO
    {
        return LoginDTO::fromArray($this->validated());
    }
}
