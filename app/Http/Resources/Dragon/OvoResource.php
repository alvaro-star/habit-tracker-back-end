<?php

namespace App\Http\Resources\Dragon;

use App\Models\Ovo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Ovo */
class OvoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'imagem_url' => $this->imagem_url,
        ];
    }
}
