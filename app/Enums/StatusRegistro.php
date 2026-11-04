<?php

namespace App\Enums;

enum StatusRegistro: string
{
    case CONCLUIDO = 'CONCLUIDO';
    case EM_ANDAMENTO = 'EM_ANDAMENTO';
    case NAO_CONCLUIDO = 'NAO_CONCLUIDO';
}
