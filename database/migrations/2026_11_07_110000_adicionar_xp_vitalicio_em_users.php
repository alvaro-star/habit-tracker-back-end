<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * XP vitalício da conta (docs/DER.md, usuario.xp_vitalicio): soma de todo o
 * XP diário ganho, que nunca é zerado, nem no renascimento do dragão.
 * Os usuários que já existem começam com 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('xp_vitalicio')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('xp_vitalicio');
        });
    }
};
