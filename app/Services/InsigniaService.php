<?php

namespace App\Services;

use App\Models\Insignia;
use App\Repositories\InsigniaRepository;

/** @extends BaseService<Insignia> */
class InsigniaService extends BaseService
{
    public function __construct(InsigniaRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
