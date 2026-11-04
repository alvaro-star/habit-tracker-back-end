<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estagio', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 30);
            $table->integer('ordem');
            $table->integer('xp_marco');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estagio');
    }
};
