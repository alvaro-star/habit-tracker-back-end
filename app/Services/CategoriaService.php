<?php

namespace App\Services;

use App\Repositories\CategoriaRepository;

class CategoriaService extends BaseService
{
    public function __construct(CategoriaRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
