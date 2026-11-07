<?php

namespace App\Http\Resources\Dragon;

use App\Models\Dragao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Dragao */
class DragaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fk_id_ovo' => $this->fk_id_ovo,
            'fk_id_estagio' => $this->fk_id_estagio,
            'nome' => $this->nome,
            'xp_ciclo' => $this->xp_ciclo,
            'status' => $this->status,
            'data_nascimento' => $this->data_nascimento->toDateString(),
            'data_termino' => $this->data_termino?->toDateString(),
            'arte_final_url' => $this->arte_final_url,
            'tempo_total_dias' => $this->tempo_total_dias,
        ];
    }
}
