<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elemento', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 20)->unique();
            $table->string('cenario_url', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elemento');
    }
};
