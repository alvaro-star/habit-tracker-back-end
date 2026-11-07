<?php

namespace App\Repositories;

use App\Models\User;

class UsuarioRepository extends BaseRepository
{
    protected string $model = User::class;

    /**
     * Soma (ou subtrai, se negativa) a diferença ao xp_vitalicio, sem deixá-lo
     * abaixo de zero. Usa forceFill porque o xp_vitalicio fica fora do Fillable.
     */
    public function somarXpVitalicio(int $usuarioId, int $diferenca): void
    {
        /** @var User $usuario */
        $usuario = $this->buscarOuFalhar($usuarioId);
        $usuario->forceFill(['xp_vitalicio' => max(0, $usuario->xp_vitalicio + $diferenca)])->save();
    }
}
