<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding (MOD8-TASK3): dragao.ovo guarda o ovo escolhido (identificador
 * definido pelo front-end; não há tabela de ovo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dragao', function (Blueprint $table) {
            $table->string('ovo', 40)->nullable();
        });

        // Quem já tem um dragão ativo usava o app antes do onboarding existir: não passa pelo fluxo.
        DB::table('users')->whereNotNull('fk_id_dragao')->update(['onboarding_concluido' => true]);
    }

    public function down(): void
    {
        Schema::table('dragao', function (Blueprint $table) {
            $table->dropColumn('ovo');
        });
    }
};
