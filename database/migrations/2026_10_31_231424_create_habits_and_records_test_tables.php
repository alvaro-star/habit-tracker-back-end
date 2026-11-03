<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categoria')) {
            Schema::create('categoria', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 40)->unique();
            });
        }

        if (! Schema::hasTable('habito')) {
            Schema::create('habito', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fk_id_usuario')->constrained('users')->cascadeOnDelete();
                $table->foreignId('fk_id_categoria')->constrained('categoria');
                $table->string('nome', 80);
                $table->string('polaridade')->default('BOM');
                $table->string('tipo_meta')->default('REALIZACAO');
                $table->string('frequencia')->default('DIARIA');
                $table->decimal('valor_meta', 10, 2)->nullable();
                $table->string('unidade', 20)->nullable();
                $table->string('dificuldade')->default('FACIL');
                $table->integer('peso')->default(5);
                $table->boolean('ativo')->default(true);
                $table->timestamp('data_criacao')->useCurrent();
            });
        }

        if (! Schema::hasTable('registro_habito')) {
            Schema::create('registro_habito', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fk_id_habito')->constrained('habito')->cascadeOnDelete();
                $table->date('data');
                $table->string('status')->default('CONCLUIDO');
                $table->decimal('valor_realizado', 10, 2)->nullable();
                $table->decimal('percentual', 5, 2)->default(100.00);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_habito');
        Schema::dropIfExists('habito');
        Schema::dropIfExists('categoria');
    }
};
