<?php

namespace App\Http\Requests\Dragon;

use App\DTOs\Dragon\DragaoDTO;
use App\Enums\StatusDragao;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class DragaoRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            'nome' => [$obrigatorio, 'string', 'max:40'],
            /** XP do ciclo. Ele define o estágio do dragão (OVO, FILHOTE, JOVEM, ADULTO, ADULTO_FINAL), que não é enviado. */
            'xp_ciclo' => [$obrigatorio, 'integer', 'min:0'],
            'status' => [$obrigatorio, Rule::enum(StatusDragao::class)],
            'data_nascimento' => [$obrigatorio, 'date_format:Y-m-d'],
            'data_termino' => ['nullable', 'date_format:Y-m-d'],
            'arte_final_url' => ['nullable', 'string', 'max:255'],
            'tempo_total_dias' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toDto(): DragaoDTO
    {
        return DragaoDTO::fromArray($this->validated());
    }
}
