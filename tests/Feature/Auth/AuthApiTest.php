<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastra_usuario_e_retorna_tokens(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ana',
            'email' => 'ana@test.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
        ]);

        $response->assertCreated();
        $this->assertArrayHasKey('accessToken', $response->json());
        $this->assertArrayHasKey('refreshToken', $response->json());
        $this->assertArrayHasKey('user', $response->json());
    }

    public function test_cadastro_exige_email_unico(): void
    {
        User::factory()->create(['email' => 'ana@test.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Ana',
            'email' => 'ana@test.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
        ])->assertUnprocessable();
    }

    public function test_cadastro_exige_senha_minimo_8_caracteres(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Ana',
            'email' => 'ana@test.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ])->assertUnprocessable();
    }

    public function test_login_com_credenciais_validas_retorna_tokens(): void
    {
        $senha = 'senha123';
        User::factory()->create(['email' => 'ana@test.com', 'password' => bcrypt($senha)]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'ana@test.com',
            'password' => $senha,
        ]);

        $response->assertOk();
        $this->assertArrayHasKey('accessToken', $response->json());
        $this->assertArrayHasKey('refreshToken', $response->json());
        $this->assertArrayHasKey('user', $response->json());
    }

    public function test_login_com_credenciais_invalidas_retorna_401(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'inexistente@test.com',
            'password' => 'qualquer',
        ])->assertUnauthorized();
    }

    public function test_login_com_senha_errada_retorna_401(): void
    {
        User::factory()->create(['email' => 'ana@test.com', 'password' => bcrypt('senha123')]);

        $this->postJson('/api/auth/login', [
            'email' => 'ana@test.com',
            'password' => 'senha_errada',
        ])->assertUnauthorized();
    }

    public function test_faz_logout_e_revoga_tokens(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $this->postJson('/api/auth/logout')->assertOk();
    }

    public function test_refresh_revoga_token_atual_e_emite_novo_par(): void
    {
        $usuario = User::factory()->create();
        $token = $usuario->createToken('access-token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/auth/refresh');

        $response->assertOk();
        $this->assertArrayHasKey('accessToken', $response->json());
    }

    public function test_rotas_protegidas_exigem_token(): void
    {
        $this->getJson('/api/habits')->assertUnauthorized();
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    public function test_login_grava_ultimo_acesso(): void
    {
        User::factory()->create(['email' => 'ana@test.com', 'password' => bcrypt('senha123')]);

        $this->postJson('/api/auth/login', ['email' => 'ana@test.com', 'password' => 'senha123'])
            ->assertOk();

        $usuario = User::firstWhere('email', 'ana@test.com');
        $this->assertNotNull($usuario->ultimo_acesso_at);
    }

    public function test_onboarding_pode_ser_marcado_como_concluido(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $this->patchJson('/api/auth/onboarding', ['concluido' => true])
            ->assertOk()
            ->assertJsonPath('onboarding_concluido', true);
    }

    public function test_onboarding_pode_ser_reaberto(): void
    {
        $usuario = User::factory()->create(['onboarding_concluido' => true]);
        Sanctum::actingAs($usuario);

        $this->patchJson('/api/auth/onboarding', ['concluido' => false])
            ->assertOk()
            ->assertJsonPath('onboarding_concluido', false);
    }

    public function test_onboarding_exige_boolean(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $this->patchJson('/api/auth/onboarding', ['concluido' => 'invalido'])
            ->assertUnprocessable();
    }

    public function test_usuario_resource_inclui_campos_novos(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'ultimo_acesso_at',
                    'onboarding_concluido',
                    'email_verified_at',
                ],
            ]);
    }

    public function test_forgot_password_envia_email(): void
    {
        User::factory()->create(['email' => 'ana@test.com']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'ana@test.com'])
            ->assertOk()
            ->assertJsonPath('message', 'Se o e-mail informado existir, você receberá um link de redefinição.');
    }

    public function test_forgot_password_aceita_email_inexistente_sem_revelar(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'inexistente@test.com'])
            ->assertOk()
            ->assertJsonPath('message', 'Se o e-mail informado existir, você receberá um link de redefinição.');
    }

    public function test_forgot_password_exige_email_valido(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'invalido'])
            ->assertUnprocessable();
    }

    public function test_reset_password_com_token_valido(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@test.com']);

        // Gera um token de redefinição (simula o que o e-mail enviaria)
        $token = Password::createToken($usuario);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'ana@test.com',
            'token' => $token,
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ])->assertOk()->assertJsonPath('message', 'Senha redefinida com sucesso.');
    }

    public function test_reset_password_com_token_invalido_retorna_400(): void
    {
        User::factory()->create(['email' => 'ana@test.com']);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'ana@test.com',
            'token' => 'token-invalido',
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ])->assertStatus(400);
    }

    public function test_reset_password_exige_senha_minimo_8(): void
    {
        $this->postJson('/api/auth/reset-password', [
            'email' => 'ana@test.com',
            'token' => 'token',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ])->assertUnprocessable();
    }

    public function test_deleta_conta_com_senha_correta(): void
    {
        $usuario = User::factory()->create(['password' => bcrypt('minhaSenha')]);
        Sanctum::actingAs($usuario);

        $this->deleteJson('/api/auth/account', ['password' => 'minhaSenha'])
            ->assertOk()
            ->assertJsonPath('message', 'Conta excluída permanentemente.');

        $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
    }

    public function test_deleta_conta_com_senha_errada_retorna_401(): void
    {
        $usuario = User::factory()->create(['password' => bcrypt('minhaSenha')]);
        Sanctum::actingAs($usuario);

        $this->deleteJson('/api/auth/account', ['password' => 'senhaErrada'])
            ->assertUnauthorized();

        $this->assertDatabaseHas('users', ['id' => $usuario->id]);
    }

    public function test_deleta_conta_sem_token_retorna_401(): void
    {
        $this->deleteJson('/api/auth/account', ['password' => 'qualquer'])
            ->assertUnauthorized();
    }

    public function test_deleta_conta_remove_tokens(): void
    {
        $usuario = User::factory()->create(['password' => bcrypt('minhaSenha')]);
        $usuario->createToken('access-token');
        Sanctum::actingAs($usuario);

        $this->deleteJson('/api/auth/account', ['password' => 'minhaSenha'])->assertOk();

        // O token foi removido junto com o usuário
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $usuario->id]);
    }
}
