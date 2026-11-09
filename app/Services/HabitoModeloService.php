<?php

namespace App\Services;

use App\Models\HabitoModelo;
use App\Repositories\HabitoModeloRepository;

/** @extends BaseService<HabitoModelo> */
class HabitoModeloService extends BaseService
{
    public function __construct(HabitoModeloRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
