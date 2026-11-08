<?php

namespace App\Enums;

/** Tipos de gatilho que o sistema dispara sozinho (gatilho_notificacao.tipo). */
enum TipoGatilho: string
{
    case STREAK_EM_RISCO = 'STREAK_EM_RISCO';
    case INATIVIDADE_3_DIAS = 'INATIVIDADE_3_DIAS';
    case INATIVIDADE_7_DIAS = 'INATIVIDADE_7_DIAS';
}
