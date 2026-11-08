<?php

namespace App\Repositories;

use App\Enums\TipoGatilho;
use App\Models\GatilhoNotificacao;

class GatilhoNotificacaoRepository extends BaseRepository
{
    protected string $model = GatilhoNotificacao::class;

    public function doTipo(TipoGatilho $tipo): ?GatilhoNotificacao
    {
        return $this->consulta()->where('tipo', $tipo->value)->first();
    }
}
