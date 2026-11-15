<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Catálogos (gatilhos de notificação e biblioteca de hábitos) e os dados do
     * servidor de testes, com o usuário admin@gmail.com / senha123.
     */
    public function run(): void
    {
        $this->call([
            GatilhoNotificacaoSeeder::class,
            BibliotecaSeeder::class,
            DadosDeTesteSeeder::class,
        ]);
    }
}
