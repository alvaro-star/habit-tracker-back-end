<?php

return [
    /*
     * A partir de que horário o aviso de streak em risco pode sair, para quem não
     * configurou um. Evita os disparos das 00:00 e das 04:00. Como todos os
     * horários da aplicação, está no fuso de Campo Grande.
     */
    'horario_streak_em_risco' => '08:00',

    /* Sem credenciais do Firebase, as notificações ficam só na tabela notifications. */
    'push_ativo' => filled(env('FIREBASE_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS'))),

    'titulo_push' => 'Habit Tracker',
];
