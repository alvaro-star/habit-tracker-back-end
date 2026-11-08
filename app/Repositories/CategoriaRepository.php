<?php

namespace App\Repositories;

use App\Models\Categoria;

/** @extends BaseRepository<Categoria> */
class CategoriaRepository extends BaseRepository
{
    protected string $model = Categoria::class;
}
