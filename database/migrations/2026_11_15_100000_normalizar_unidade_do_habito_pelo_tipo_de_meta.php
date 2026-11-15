<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Unidade customizada (MOD3-TASK8): só as metas de QUANTIDADE têm unidade
 * definida pelo usuário. Acerta os hábitos já gravados: TEMPO passa a usar
 * "minutos" e REALIZACAO fica sem unidade, como o HabitoService faz agora.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('habito')->where('tipo_meta', 'TEMPO')->update(['unidade' => 'minutos']);
        DB::table('habito')->where('tipo_meta', 'REALIZACAO')->update(['unidade' => null]);
    }

    public function down(): void
    {
        // Só normaliza dados: as unidades antigas desses tipos não são recuperáveis.
    }
};
