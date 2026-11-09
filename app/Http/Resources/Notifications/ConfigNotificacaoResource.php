<?php

namespace App\Http\Resources\Notifications;

use App\Models\ConfigNotificacao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ConfigNotificacao */
class ConfigNotificacaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'fk_id_gatilho' => $this->fk_id_gatilho,
            'tipo' => $this->gatilho->tipo,
            'canal' => $this->gatilho->canal,
            'frequencia' => $this->gatilho->frequencia,
            'ativo' => $this->ativo,
            // O Postgres devolve HH:MM:SS; a API usa sempre HH:MM.
            'horario' => $this->horario !== null ? substr($this->horario, 0, 5) : null,
        ];
    }
}
