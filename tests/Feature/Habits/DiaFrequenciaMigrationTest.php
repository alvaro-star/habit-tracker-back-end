<?php

namespace Tests\Feature\Habits;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DiaFrequenciaMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_preenche_os_habitos_semanais_e_mensais_existentes(): void
    {
        $migration = require database_path('migrations/2026_11_07_100000_adicionar_dia_frequencia_em_habito.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('habito', 'dia_frequencia'));

        $base = [
            'fk_id_usuario' => User::factory()->create()->id,
            'fk_id_categoria' => Categoria::create(['nome' => 'Geral'])->id,
            'nome' => 'Hábito antigo',
        ];
        $diario = DB::table('habito')->insertGetId([...$base, 'frequencia' => 'DIARIA']);
        $semanal = DB::table('habito')->insertGetId([...$base, 'frequencia' => 'SEMANAL']);
        $mensal = DB::table('habito')->insertGetId([...$base, 'frequencia' => 'MENSAL']);

        $migration->up();

        $this->assertDatabaseHas('habito', ['id' => $diario, 'dia_frequencia' => null]);
        $this->assertDatabaseHas('habito', ['id' => $semanal, 'dia_frequencia' => 'SEG']);
        $this->assertDatabaseHas('habito', ['id' => $mensal, 'dia_frequencia' => '1']);
    }
}
