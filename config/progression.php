<?php

/*
|--------------------------------------------------------------------------
| Progressão e XP (MOD5)
|--------------------------------------------------------------------------
|
| Parâmetros fixos das regras de XP definidas em docs/ESCOPO.md (seção 3.3).
| São regras do jogo, e não da instalação, por isso não vêm do .env.
|
*/

return [

    /*
     * Teto diário de XP (TETO_DIÁRIO): o máximo que um usuário ganha em um
     * dia perfeito. XP do dia = (∑W cumpridos / ∑W total) × teto_diario.
     * Com 25 XP, os 9.000 XP do ciclo do dragão levam cerca de 360 dias.
     */
    'teto_diario' => 25,

];
