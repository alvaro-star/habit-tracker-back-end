<?php

namespace Tests\Feature;

use App\Enums\StatusDragao;
use App\Models\Categoria;
use App\Models\Dragao;
use App\Models\HabitoModelo;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Models\XpDiario;
use Database\Seeders\BibliotecaSeeder;
use Database\Seeders\DadosDeTesteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_cria_a_biblioteca_e_os_dados_do_admin(): void
    {
        $this->seed();

        $this->assertSame(count(BibliotecaSeeder::CATEGORIAS), Categoria::count());
        $this->assertSame(13, HabitoModelo::count());

        $admin = User::where('email', DadosDeTesteSeeder::EMAIL)->firstOrFail();
        $this->assertTrue(Hash::check(DadosDeTesteSeeder::SENHA, $admin->password));
        $this->assertTrue($admin->onboarding_concluido);

        /** @var Dragao $ativo */
        $ativo = $admin->dragaoAtivo;
        $this->assertSame(StatusDragao::ATIVO, $ativo->status);
        $this->assertGreaterThan(0, $ativo->xp_ciclo);
        $this->assertSame($ativo->xp_ciclo, $admin->xp_vitalicio);
        $this->assertSame(1, Dragao::where('status', StatusDragao::SANTUARIO->value)->count());

        $this->assertSame(7, $admin->habitos()->count());
        $this->assertGreaterThan(50, RegistroHabito::count());
        $this->assertSame((int) XpDiario::sum('xp_ganho'), $ativo->xp_ciclo);
    }

    public function test_seed_pode_rodar_de_novo_sem_duplicar_os_dados(): void
    {
        $this->seed();
        $registros = RegistroHabito::count();

        $this->seed();

        $this->assertSame(1, User::where('email', DadosDeTesteSeeder::EMAIL)->count());
        $this->assertSame(13, HabitoModelo::count());
        $this->assertSame($registros, RegistroHabito::count());
    }

    public function test_admin_consegue_fazer_login_com_a_senha_do_seed(): void
    {
        $this->seed();

        $this->postJson('/api/auth/login', [
            'email' => DadosDeTesteSeeder::EMAIL,
            'password' => DadosDeTesteSeeder::SENHA,
        ])->assertOk();
    }
}
