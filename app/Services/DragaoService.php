<?php

namespace App\Services;

use App\Repositories\DragaoRepository;

class DragaoService extends BaseService
{
    public function __construct(DragaoRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
