<?php

namespace App\Services;

use App\Repositories\OvoRepository;

class OvoService extends BaseService
{
    public function __construct(OvoRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
