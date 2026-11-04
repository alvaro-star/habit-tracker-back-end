<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escolha_elemento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_id_dragao')->constrained('dragao')->cascadeOnDelete();
            $table->foreignId('fk_id_elemento')->constrained('elemento');
            $table->foreignId('fk_id_estagio')->constrained('estagio');
            $table->timestamp('data_escolha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escolha_elemento');
    }
};
