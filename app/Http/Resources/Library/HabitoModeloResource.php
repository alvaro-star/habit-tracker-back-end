<?php

namespace App\Http\Resources\Library;

use App\Models\HabitoModelo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HabitoModelo */
class HabitoModeloResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fk_id_categoria' => $this->fk_id_categoria,
            'nome' => $this->nome,
            'tipo_meta' => $this->tipo_meta,
            'meta_padrao' => $this->meta_padrao,
            'unidade_padrao' => $this->unidade_padrao,
            'dificuldade_padrao' => $this->dificuldade_padrao,
            'prioridade_mvp' => $this->prioridade_mvp,
        ];
    }
}
