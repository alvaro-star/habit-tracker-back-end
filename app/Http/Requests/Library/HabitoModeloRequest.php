<?php

namespace App\Http\Requests\Library;

use App\DTOs\Library\HabitoModeloDTO;
use App\Enums\Dificuldade;
use App\Enums\TipoMeta;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class HabitoModeloRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            'fk_id_categoria' => [$obrigatorio, 'integer', 'exists:categoria,id'],
            'nome' => [$obrigatorio, 'string', 'max:80'],
            'tipo_meta' => [$obrigatorio, Rule::enum(TipoMeta::class)],
            'meta_padrao' => [$obrigatorio, 'numeric', 'min:0', 'max:99999999.99'],
            'unidade_padrao' => [$obrigatorio, 'string', 'max:20'],
            'dificuldade_padrao' => [$obrigatorio, Rule::enum(Dificuldade::class)],
            'prioridade_mvp' => [$obrigatorio, 'boolean'],
        ];
    }

    public function toDto(): HabitoModeloDTO
    {
        return HabitoModeloDTO::fromArray($this->validated());
    }
}
