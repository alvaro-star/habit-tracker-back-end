<?php

namespace App\Http\Resources\Notifications;

use App\Models\GatilhoNotificacao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GatilhoNotificacao */
class GatilhoNotificacaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'canal' => $this->canal,
            'frequencia' => $this->frequencia,
            'texto_base' => $this->texto_base,
        ];
    }
}
