<?php

namespace App\Services;

use App\Repositories\NivelRepository;

class NivelService extends BaseService
{
    public function __construct(NivelRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
