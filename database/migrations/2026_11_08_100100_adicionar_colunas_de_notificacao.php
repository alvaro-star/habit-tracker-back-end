<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('ultimo_acesso')->nullable();
            $table->unsignedInteger('streak_atual')->default(0);
        });

        Schema::table('habito', function (Blueprint $table) {
            $table->time('horario_lembrete')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ultimo_acesso', 'streak_atual']);
        });

        Schema::table('habito', function (Blueprint $table) {
            $table->dropColumn('horario_lembrete');
        });
    }
};
