<?php

namespace App\Services;

use App\Models\GatilhoNotificacao;
use App\Repositories\GatilhoNotificacaoRepository;

/** @extends BaseService<GatilhoNotificacao> */
class GatilhoNotificacaoService extends BaseService
{
    public function __construct(GatilhoNotificacaoRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
