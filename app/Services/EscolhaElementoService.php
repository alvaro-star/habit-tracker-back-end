<?php

namespace App\Services;

use App\Repositories\EscolhaElementoRepository;

class EscolhaElementoService extends BaseService
{
    public function __construct(EscolhaElementoRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
