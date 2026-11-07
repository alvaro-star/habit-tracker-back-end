<?php

namespace App\Http\Resources\Dragon;

use App\Models\EscolhaElemento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EscolhaElemento */
class EscolhaElementoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fk_id_dragao' => $this->fk_id_dragao,
            'elemento' => $this->elemento,
            'estagio' => $this->estagio,
            'data_escolha' => $this->data_escolha,
        ];
    }
}
