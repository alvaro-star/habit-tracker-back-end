<?php

namespace App\Enums;

enum TipoMeta: string
{
    case REALIZACAO = 'REALIZACAO';
    case TEMPO = 'TEMPO';
    case QUANTIDADE = 'QUANTIDADE';

    /** Unidade das metas de tempo (ESCOPO §2.1: "inserção manual de minutos"). */
    public const UNIDADE_TEMPO = 'minutos';

    /** Só as metas de quantidade têm unidade definida pelo usuário (MOD3-TASK8). */
    public function aceitaUnidadeCustomizada(): bool
    {
        return $this === self::QUANTIDADE;
    }

    /**
     * Unidade gerada pelo sistema para os tipos sem unidade customizada:
     * minutos no TEMPO e nenhuma na REALIZACAO, que não tem meta numérica.
     */
    public function unidadeFixa(): ?string
    {
        return $this === self::TEMPO ? self::UNIDADE_TEMPO : null;
    }
}
