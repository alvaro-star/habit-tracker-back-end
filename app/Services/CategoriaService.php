<?php

namespace App\Services;

use App\Models\Categoria;
use App\Repositories\CategoriaRepository;

/** @extends BaseService<Categoria> */
class CategoriaService extends BaseService
{
    public function __construct(CategoriaRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
