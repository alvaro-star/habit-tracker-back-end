<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\CadastroDTO;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class CadastroRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            'name' => [$obrigatorio, 'string', 'max:80'],
            /**
             * E-mail único: o cadastro valida duplicatas.
             */
            'email' => [$obrigatorio, 'email', Rule::unique('users', 'email')],
            'password' => [$obrigatorio, 'string', 'min:8', 'confirmed'],
        ];
    }

    public function toDto(): CadastroDTO
    {
        return CadastroDTO::fromArray($this->validated());
    }
}
