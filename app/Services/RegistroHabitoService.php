<?php

namespace App\Services;

use App\Repositories\RegistroHabitoRepository;

class RegistroHabitoService extends BaseService
{
    public function __construct(RegistroHabitoRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
