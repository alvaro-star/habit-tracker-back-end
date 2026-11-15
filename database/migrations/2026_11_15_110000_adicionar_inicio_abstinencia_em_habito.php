<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hábitos ruins (MOD3-TASK4 / MOD7-TASK9): habito.inicio_abstinencia guarda
 * desde quando o usuário está sem praticar o hábito ruim. Só os hábitos RUIM
 * têm o campo; nos hábitos BOM ele fica nulo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habito', function (Blueprint $table) {
            $table->timestamp('inicio_abstinencia')->nullable();
        });

        // Os hábitos ruins já cadastrados começam a contar da data de criação.
        DB::table('habito')->where('polaridade', 'RUIM')->update(['inicio_abstinencia' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('habito', function (Blueprint $table) {
            $table->dropColumn('inicio_abstinencia');
        });
    }
};
