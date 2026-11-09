<?php

namespace App\Repositories;

use App\Models\HabitoModelo;

/** @extends BaseRepository<HabitoModelo> */
class HabitoModeloRepository extends BaseRepository
{
    protected string $model = HabitoModelo::class;
}
