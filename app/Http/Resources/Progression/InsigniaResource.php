<?php

namespace App\Http\Resources\Progression;

use App\Models\Insignia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Insignia */
class InsigniaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'icone_url' => $this->icone_url,
        ];
    }
}
