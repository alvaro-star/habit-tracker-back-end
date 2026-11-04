<?php

namespace App\Services;

use App\Repositories\ElementoRepository;

class ElementoService extends BaseService
{
    public function __construct(ElementoRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
