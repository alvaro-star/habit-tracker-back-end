<?php

namespace App\Services;

use App\Repositories\EstagioRepository;

class EstagioService extends BaseService
{
    public function __construct(EstagioRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
