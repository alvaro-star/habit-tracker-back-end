<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ovo', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 40);
            $table->string('imagem_url', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ovo');
    }
};
