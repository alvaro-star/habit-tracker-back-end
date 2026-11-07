<?php

namespace App\Repositories;

use App\Models\EscolhaElemento;
use Illuminate\Database\Eloquent\Builder;

class EscolhaElementoRepository extends BaseRepository
{
    protected string $model = EscolhaElemento::class;

    /** O dono da escolha é o dono do dragão a que ela pertence. */
    protected function consultaDoUsuario(int $usuarioId): Builder
    {
        return $this->consulta()->whereHas('dragao', fn ($dragao) => $dragao->where('fk_id_usuario', $usuarioId));
    }
}
