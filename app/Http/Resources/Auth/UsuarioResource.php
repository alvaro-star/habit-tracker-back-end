<?php

namespace App\Http\Resources\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UsuarioResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'xp_vitalicio' => $this->xp_vitalicio,
            'fk_id_dragao' => $this->fk_id_dragao,
            // O nível não é coluna: é calculado pelo XP ao montar a resposta.
            'nivel' => $this->getNivel(),
            'ultimo_acesso_at' => $this->ultimo_acesso_at?->toISOString(),
            'onboarding_concluido' => $this->onboarding_concluido,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
