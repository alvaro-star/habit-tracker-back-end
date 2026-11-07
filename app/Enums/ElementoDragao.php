<?php

namespace App\Enums;

/**
 * Elemento (bioma) do dragão, escolhido a partir dos 6.000 XP do ciclo
 * (ESCOPO.md, seção 4.2). O cenário de fundo e a aparência da forma final de
 * cada elemento ficam a cargo do front-end.
 */
enum ElementoDragao: string
{
    case FOGO = 'FOGO';
    case TERRA = 'TERRA';
    case AGUA = 'AGUA';
    case AR = 'AR';
}
