<?php

namespace App\Http\Resources\Dragon;

use App\Models\Elemento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Elemento */
class ElementoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'cenario_url' => $this->cenario_url,
        ];
    }
}
