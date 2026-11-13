<?php

namespace App\Http\Resources\Dragon;

use App\Models\Dragao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Entrada do Santuário: um dragão que concluiu o ciclo (ESCOPO.md, seção 4.3).
 *
 * @mixin Dragao
 */
class SantuarioResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            // Arte do dragão adulto no seu bioma; o elemento final define o bioma no front-end.
            'arte_final_url' => $this->arte_final_url,
            'elemento' => $this->elemento,
            'data_nascimento' => $this->data_nascimento->toDateString(),
            'data_termino' => $this->data_termino?->toDateString(),
            // Dias entre o nascimento e o término do ciclo.
            'tempo_total_dias' => $this->tempo_total_dias,
            // Elementos escolhidos ao longo do ciclo, do primeiro para o último.
            'trajetoria' => EscolhaElementoResource::collection($this->escolhasElemento),
        ];
    }
}
