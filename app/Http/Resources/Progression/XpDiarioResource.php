<?php

namespace App\Http\Resources\Progression;

use App\Models\XpDiario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin XpDiario */
class XpDiarioResource extends JsonResource
{
    /** A coluna `data` colidiria com o envelope `{"data": ...}` do Laravel. */
    public static bool $forceWrapping = true;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fk_id_dragao' => $this->fk_id_dragao,
            'data' => $this->data->toDateString(),
            'percentual_ponderado' => $this->percentual_ponderado,
            'xp_ganho' => $this->xp_ganho,
        ];
    }
}
