<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Padroniza a data de criação dos hábitos no created_at do Laravel,
 * o mesmo nome usado nos demais models.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('habito', 'data_criacao')) {
            Schema::table('habito', function (Blueprint $table) {
                $table->renameColumn('data_criacao', 'created_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('habito', 'created_at')) {
            Schema::table('habito', function (Blueprint $table) {
                $table->renameColumn('created_at', 'data_criacao');
            });
        }
    }
};
