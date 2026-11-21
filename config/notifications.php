<?php

return [
    /*
     * Horário padrão do aviso de streak em risco, para quem não configurou um.
     * Como todos os horários da aplicação, está no fuso de Campo Grande.
     */
    'horario_streak_em_risco' => '19:00',

    /* Sem credenciais do Firebase, as notificações ficam só na tabela notifications. */
    'push_ativo' => filled(env('FIREBASE_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS'))),

    'titulo_push' => 'Habit Tracker',
];
