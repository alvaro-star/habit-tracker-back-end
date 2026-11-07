<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove a tabela nivel: o nível do usuário passa a ser calculado pelo
 * NivelService a partir do xp_vitalicio, com a curva de config/progression.php,
 * sem consultar o banco.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('nivel');
    }

    public function down(): void
    {
        Schema::create('nivel', function (Blueprint $table) {
            $table->id();
            $table->integer('xp_necessario');
            $table->integer('xp_acumulado');
        });
    }
};
