<?php

namespace App\Services;

use App\Repositories\InsigniaRepository;

class InsigniaService extends BaseService
{
    public function __construct(InsigniaRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
