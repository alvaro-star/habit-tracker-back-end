<?php

namespace App\Http\Resources\Dragon;

use App\Models\Estagio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Estagio */
class EstagioResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'ordem' => $this->ordem,
            'xp_marco' => $this->xp_marco,
        ];
    }
}
