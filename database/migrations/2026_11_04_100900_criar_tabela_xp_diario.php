<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xp_diario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_id_usuario')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fk_id_dragao')->constrained('dragao')->cascadeOnDelete();
            $table->date('data');
            $table->decimal('percentual_ponderado', 5, 2);
            $table->integer('xp_ganho');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_diario');
    }
};
