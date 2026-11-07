<?php

namespace Tests\Feature\Progression;

use App\Models\User;
use App\Services\NivelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NivelVitalicioTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioComXp(int $xp): User
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['xp_vitalicio' => $xp])->save();

        return $usuario;
    }

    public function test_curva_tem_30_niveis_somando_9000_xp(): void
    {
        $curva = app(NivelService::class)->curva();

        $this->assertCount(30, $curva);
        $this->assertSame(['nivel' => 1, 'xp_necessario' => 0, 'xp_inicial' => 0], $curva[0]);
        $this->assertSame(['nivel' => 2, 'xp_necessario' => 61, 'xp_inicial' => 61], $curva[1]);
        $this->assertSame(9000, $curva[29]['xp_inicial']);
        $this->assertSame(9000, array_sum(array_column($curva, 'xp_necessario')));
    }

    public function test_cada_nivel_exige_cerca_de_10_por_cento_a_mais(): void
    {
        $passos = array_slice(array_column(app(NivelService::class)->curva(), 'xp_necessario'), 1);

        for ($i = 1; $i < count($passos); $i++) {
            $this->assertEqualsWithDelta(1.10, $passos[$i] / $passos[$i - 1], 0.02, "passo {$i}: {$passos[$i - 1]} → {$passos[$i]}");
        }
    }

    /** @return array<string, array{0: int, 1: int}> */
    public static function xpENivel(): array
    {
        return [
            'sem XP' => [0, 1],
            'um XP antes do nível 2' => [60, 1],
            'no início do nível 2' => [61, 2],
            'no meio da curva (nível 11 começa em 967 XP)' => [1000, 11],
            'um XP antes do máximo' => [8999, 29],
            'no máximo' => [9000, 30],
            'acima do máximo' => [20000, 30],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('xpENivel')]
    public function test_nivel_do_xp_pelos_intervalos(int $xp, int $nivel): void
    {
        $this->assertSame($nivel, app(NivelService::class)->nivelDoXp($xp));
        $this->assertSame($nivel, $this->usuarioComXp($xp)->getNivel());
    }

    public function test_tabela_nivel_nao_existe_mais(): void
    {
        $this->assertFalse(Schema::hasTable('nivel'));
    }

    public function test_usuario_logado_traz_o_nivel_na_resposta(): void
    {
        $usuario = $this->usuarioComXp(1000);
        Sanctum::actingAs($usuario);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $usuario->id)
            ->assertJsonPath('data.xp_vitalicio', 1000)
            ->assertJsonPath('data.nivel', 11)
            ->assertJsonMissingPath('data.password');
    }

    public function test_usuario_logado_exige_autenticacao(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }
}
