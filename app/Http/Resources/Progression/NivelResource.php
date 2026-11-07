<?php

namespace App\Http\Resources\Progression;

use App\Models\Nivel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Nivel */
class NivelResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'xp_necessario' => $this->xp_necessario,
            'xp_acumulado' => $this->xp_acumulado,
        ];
    }
}
