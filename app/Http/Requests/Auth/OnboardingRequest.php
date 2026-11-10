<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;
use App\DTOs\Auth\OnboardingDTO;

/**
 * @ignoreSchema O corpo é gerado por operação.
 */
class OnboardingRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'concluido' => ['required', 'boolean'],
        ];
    }

    public function toDto(): OnboardingDTO
    {
        return OnboardingDTO::fromArray($this->validated());
    }
}
