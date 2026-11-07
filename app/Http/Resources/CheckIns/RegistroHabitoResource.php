<?php

namespace App\Http\Resources\CheckIns;

use App\Models\RegistroHabito;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RegistroHabito */
class RegistroHabitoResource extends JsonResource
{
    /**
     * O registro tem uma coluna chamada `data`, que o Laravel confundiria com o
     * envelope `{"data": ...}` e deixaria de embrulhar a resposta.
     */
    public static bool $forceWrapping = true;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fk_id_habito' => $this->fk_id_habito,
            'data' => $this->data->toDateString(),
            'status' => $this->status,
            'valor_realizado' => $this->valor_realizado,
            'percentual' => $this->percentual,
        ];
    }
}
