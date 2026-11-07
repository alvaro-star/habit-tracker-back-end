<?php

namespace App\Http\Requests\Library;

use App\DTOs\Library\CategoriaDTO;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class CategoriaRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'nome' => [$this->obrigatorio(), 'string', 'max:40', Rule::unique('categoria', 'nome')->ignore($this->route('id'))],
        ];
    }

    public function toDto(): CategoriaDTO
    {
        return CategoriaDTO::fromArray($this->validated());
    }
}
