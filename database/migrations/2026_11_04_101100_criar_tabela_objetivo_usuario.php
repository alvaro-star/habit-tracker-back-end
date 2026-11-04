<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objetivo_usuario', function (Blueprint $table) {
            $table->foreignId('fk_id_usuario')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fk_id_categoria')->constrained('categoria')->cascadeOnDelete();

            $table->primary(['fk_id_usuario', 'fk_id_categoria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objetivo_usuario');
    }
};
