<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_notificacao', function (Blueprint $table) {
            $table->foreignId('fk_id_usuario')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fk_id_gatilho')->constrained('gatilho_notificacao')->cascadeOnDelete();
            $table->boolean('ativo');
            $table->time('horario')->nullable();

            $table->primary(['fk_id_usuario', 'fk_id_gatilho']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_notificacao');
    }
};
