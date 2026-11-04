<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habito_modelo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_id_categoria')->constrained('categoria');
            $table->string('nome', 80);
            $table->string('tipo_meta');
            $table->decimal('meta_padrao', 10, 2);
            $table->string('unidade_padrao', 20);
            $table->string('dificuldade_padrao');
            $table->boolean('prioridade_mvp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habito_modelo');
    }
};
