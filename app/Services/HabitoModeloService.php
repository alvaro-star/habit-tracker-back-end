<?php

namespace App\Services;

use App\Repositories\HabitoModeloRepository;

class HabitoModeloService extends BaseService
{
    public function __construct(HabitoModeloRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
