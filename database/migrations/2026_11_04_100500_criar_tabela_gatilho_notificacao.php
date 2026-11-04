<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gatilho_notificacao', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 40);
            $table->string('canal');
            $table->string('frequencia', 40);
            $table->string('texto_base', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gatilho_notificacao');
    }
};
