<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insignia', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 60);
            $table->string('descricao', 255);
            $table->string('icone_url', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insignia');
    }
};
