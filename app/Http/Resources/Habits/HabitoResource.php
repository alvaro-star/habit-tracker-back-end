<?php

namespace App\Http\Resources\Habits;

use App\Enums\Frequencia;
use App\Models\Habito;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Habito */
class HabitoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fk_id_categoria' => $this->fk_id_categoria,
            'nome' => $this->nome,
            'polaridade' => $this->polaridade,
            'tipo_meta' => $this->tipo_meta,
            'frequencia' => $this->frequencia,
            // A coluna é texto; no mensal, o dia volta como número.
            'dia_frequencia' => $this->frequencia === Frequencia::MENSAL->value && $this->dia_frequencia !== null
                ? (int) $this->dia_frequencia
                : $this->dia_frequencia,
            'valor_meta' => $this->valor_meta,
            'unidade' => $this->unidade,
            'dificuldade' => $this->dificuldade,
            'peso' => $this->peso,
            'ativo' => $this->ativo,
            'inactivated_at' => $this->inactivated_at,
            'created_at' => $this->created_at,
        ];
    }
}
