<?php

namespace App\Http\Requests\Progression;

use App\DTOs\Progression\XpDiarioDTO;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class XpDiarioRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            // O dragão precisa pertencer ao usuário autenticado.
            'fk_id_dragao' => [$obrigatorio, 'integer', Rule::exists('dragao', 'id')->where('fk_id_usuario', $this->user()?->id)],
            'data' => [$obrigatorio, 'date_format:Y-m-d'],
            'percentual_ponderado' => [$obrigatorio, 'numeric', 'between:0,100'],
            'xp_ganho' => [$obrigatorio, 'integer', 'min:0'],
        ];
    }

    public function toDto(): XpDiarioDTO
    {
        return XpDiarioDTO::fromArray($this->validated());
    }
}
