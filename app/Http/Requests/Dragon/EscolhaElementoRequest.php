<?php

namespace App\Http\Requests\Dragon;

use App\DTOs\Dragon\EscolhaElementoDTO;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class EscolhaElementoRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            // O dragão precisa pertencer ao usuário autenticado.
            'fk_id_dragao' => [$obrigatorio, 'integer', Rule::exists('dragao', 'id')->where('fk_id_usuario', $this->user()?->id)],
            'fk_id_elemento' => [$obrigatorio, 'integer', 'exists:elemento,id'],
            'data_escolha' => [$obrigatorio, 'date'],
        ];
    }

    public function toDto(): EscolhaElementoDTO
    {
        return EscolhaElementoDTO::fromArray($this->validated());
    }
}
