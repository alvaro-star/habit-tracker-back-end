<?php

namespace App\Services;

use App\Repositories\GatilhoNotificacaoRepository;

class GatilhoNotificacaoService extends BaseService
{
    public function __construct(GatilhoNotificacaoRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
