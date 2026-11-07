<?php

namespace Tests\Feature\Progression;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XpVitalicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_novo_comeca_com_xp_vitalicio_zero(): void
    {
        $usuario = User::factory()->create()->refresh();

        $this->assertSame(0, $usuario->xp_vitalicio);
    }

    public function test_xp_vitalicio_nao_e_preenchido_em_massa(): void
    {
        $usuario = User::factory()->create();
        $usuario->fill(['xp_vitalicio' => 9000])->save();

        $this->assertSame(0, $usuario->refresh()->xp_vitalicio);
    }
}
