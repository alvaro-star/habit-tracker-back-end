<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UsuarioLogadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_devolve_o_ultimo_acesso_de_um_usuario_lido_do_banco(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['ultimo_acesso_at' => '2026-11-12 10:00:00'])->save();

        // O usuário de uma requisição real vem do banco, com o ultimo_acesso_at como texto.
        Sanctum::actingAs(User::query()->findOrFail($usuario->id));

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.ultimo_acesso_at', '2026-11-12T10:00:00.000000Z');
    }
}
