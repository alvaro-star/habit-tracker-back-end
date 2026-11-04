<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nivel', function (Blueprint $table) {
            $table->id();
            $table->integer('xp_necessario');
            $table->integer('xp_acumulado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nivel');
    }
};
