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

    /*
     * Nível vitalício (ESCOPO.md, seção 4.1; MOD5-TASK6 e TASK7): 30 níveis,
     * alcançados com 9.000 XP vitalícios no total, e cada nível exige cerca de
     * 10% de XP a mais que o anterior. O nível 1 começa em 0 XP, e os 29 passos
     * seguintes somam os 9.000 XP. O NivelService calcula os intervalos e o
     * nível de cada XP a partir destes valores, sem tabela no banco.
     */
    'niveis' => [
        'quantidade' => 30,
        'xp_total' => 9000,
        'crescimento' => 0.10,
    ],

];
