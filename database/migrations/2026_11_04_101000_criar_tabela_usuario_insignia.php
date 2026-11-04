<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_insignia', function (Blueprint $table) {
            $table->foreignId('fk_id_usuario')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fk_id_insignia')->constrained('insignia')->cascadeOnDelete();
            $table->timestamp('data_conquista');

            $table->primary(['fk_id_usuario', 'fk_id_insignia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_insignia');
    }
};
