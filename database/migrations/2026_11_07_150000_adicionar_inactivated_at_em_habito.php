<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Momento em que o hábito foi desativado (null enquanto ele está ativo).
 * Serve para, no futuro, desconsiderar o hábito só a partir dessa data no
 * XP diário e no progresso dos hábitos (veja os TODOs no HabitoRepository).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habito', function (Blueprint $table) {
            $table->timestamp('inactivated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('habito', function (Blueprint $table) {
            $table->dropColumn('inactivated_at');
        });
    }
};
