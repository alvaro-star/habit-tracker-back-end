<?php

namespace App\Services;

use App\Repositories\HabitoRepository;

class HabitoService extends BaseService
{
    public function __construct(HabitoRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
