<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\RefreshDTO;
use App\Http\Requests\ApiRequest;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class RefreshRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            /** Token FCM do dispositivo. Se for nulo ou não for enviado, o token anterior é mantido. */
            'token_dispositivo' => ['nullable', 'string', 'max:512'],
        ];
    }

    public function toDto(): RefreshDTO
    {
        return RefreshDTO::fromArray($this->validated());
    }
}
