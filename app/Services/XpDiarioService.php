<?php

namespace App\Services;

use App\Repositories\XpDiarioRepository;

class XpDiarioService extends BaseService
{
    public function __construct(XpDiarioRepository $repositorio)
    {
        parent::__construct($repositorio);
    }
}
