<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice composto (fk_id_habito, data) em registro_habito: o POST de check-in
 * procura o registro do hábito no dia por essas duas colunas antes de criar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_habito', function (Blueprint $table) {
            $table->index(['fk_id_habito', 'data'], 'registro_habito_habito_data_index');
        });
    }

    public function down(): void
    {
        Schema::table('registro_habito', function (Blueprint $table) {
            $table->dropIndex('registro_habito_habito_data_index');
        });
    }
};
