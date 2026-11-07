<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dia em que o hábito deve ser cumprido, conforme a frequência:
 * null no diário, SEG a DOM no semanal e "1" a "31" no mensal.
 * Os hábitos semanais e mensais que já existem recebem SEG e o dia 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habito', function (Blueprint $table) {
            $table->string('dia_frequencia', 3)->nullable()->after('frequencia');
        });

        DB::table('habito')->where('frequencia', 'SEMANAL')->whereNull('dia_frequencia')->update(['dia_frequencia' => 'SEG']);
        DB::table('habito')->where('frequencia', 'MENSAL')->whereNull('dia_frequencia')->update(['dia_frequencia' => '1']);
    }

    public function down(): void
    {
        Schema::table('habito', function (Blueprint $table) {
            $table->dropColumn('dia_frequencia');
        });
    }
};
