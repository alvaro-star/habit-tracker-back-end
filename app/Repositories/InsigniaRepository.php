<?php

namespace App\Repositories;

use App\Models\Insignia;

/** @extends BaseRepository<Insignia> */
class InsigniaRepository extends BaseRepository
{
    protected string $model = Insignia::class;
}
