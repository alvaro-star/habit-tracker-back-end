<?php

namespace App\Http\Requests\Dragon;

use App\DTOs\Dragon\DragaoDTO;
use App\Enums\ElementoDragao;
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
            /**
             * Elemento do dragão: FOGO, TERRA, AGUA ou AR (o cenário e a aparência ficam com o front-end).
             * Obrigatório no PUT/PATCH quando o dragão tem 6.000 XP ou mais no ciclo e ainda não tem
             * elemento. Cada troca é registrada na trajetória (escolha_elemento).
             */
            'elemento' => ['sometimes', 'nullable', Rule::enum(ElementoDragao::class)],
            'nome' => [$obrigatorio, 'string', 'max:40'],
            /** Ovo de que o dragão nasceu: identificador definido pelo front-end (ex.: `ovo_azul`). */
            'ovo' => ['sometimes', 'nullable', 'string', 'max:40'],
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
