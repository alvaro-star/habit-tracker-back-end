<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dragao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_id_usuario')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fk_id_ovo')->constrained('ovo');
            $table->foreignId('fk_id_estagio')->constrained('estagio');
            $table->string('nome', 40);
            $table->integer('xp_ciclo');
            $table->string('status');
            $table->date('data_nascimento');
            $table->date('data_termino')->nullable();
            $table->string('arte_final_url', 255)->nullable();
            $table->integer('tempo_total_dias')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dragao');
    }
};
