<?php

namespace App\Http\Resources\Dragon;

use App\Enums\EstagioDragao;
use App\Models\Dragao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Dragao */
class DragaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            // Ovo escolhido no onboarding (identificador do front-end); null nos dragões sem ovo registrado.
            'ovo' => $this->ovo,
            'xp_ciclo' => $this->xp_ciclo,
            'estagio' => $this->estagio,
            // Elemento atual: com o estágio, o front-end define o cenário e a aparência (ADULTO_FINAL).
            'elemento' => $this->elemento,
            // true quando o dragão já passou dos 6.000 XP e o usuário precisa escolher o elemento.
            'precisa_escolher_elemento' => $this->elemento === null && $this->xp_ciclo >= EstagioDragao::XP_ESCOLHA_ELEMENTO,
            'status' => $this->status,
            'data_nascimento' => $this->data_nascimento->toDateString(),
            'data_termino' => $this->data_termino?->toDateString(),
            'arte_final_url' => $this->arte_final_url,
            'tempo_total_dias' => $this->tempo_total_dias,
        ];
    }
}
