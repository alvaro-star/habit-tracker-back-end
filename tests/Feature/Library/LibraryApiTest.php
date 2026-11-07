<?php

namespace Tests\Feature\Library;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\VerificaCrudDaApi;
use Tests\TestCase;

class LibraryApiTest extends TestCase
{
    use RefreshDatabase, VerificaCrudDaApi;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_rotas_exigem_autenticacao(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/library/categories')->assertUnauthorized();
        $this->getJson('/api/library/habit-templates')->assertUnauthorized();
    }

    public function test_crud_de_categorias(): void
    {
        $this->verificarCrud('/api/library/categories', ['nome' => 'Saude'], ['nome' => 'Saude e bem-estar']);
    }

    public function test_nome_da_categoria_e_unico_mas_pode_ser_mantido_na_edicao(): void
    {
        $categoria = Categoria::create(['nome' => 'Estudos']);

        $this->postJson('/api/library/categories', ['nome' => 'Estudos'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nome']);

        $this->putJson("/api/library/categories/{$categoria->id}", ['nome' => 'Estudos'])->assertOk();
    }

    public function test_crud_de_modelos_de_habito(): void
    {
        $categoria = Categoria::create(['nome' => 'Saude']);

        $this->verificarCrud('/api/library/habit-templates', [
            'fk_id_categoria' => $categoria->id,
            'nome' => 'Beber agua',
            'tipo_meta' => 'QUANTIDADE',
            'meta_padrao' => '2.00',
            'unidade_padrao' => 'litros',
            'dificuldade_padrao' => 'FACIL',
            'prioridade_mvp' => true,
        ], ['meta_padrao' => '3.00']);
    }

    public function test_valida_campos_do_modelo_de_habito(): void
    {
        $this->postJson('/api/library/habit-templates', ['tipo_meta' => 'OUTRO', 'fk_id_categoria' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'fk_id_categoria', 'nome', 'tipo_meta', 'meta_padrao',
                'unidade_padrao', 'dificuldade_padrao', 'prioridade_mvp',
            ]);
    }

    public function test_id_nao_numerico_retorna_404(): void
    {
        $this->getJson('/api/library/categories/abc')->assertNotFound();
    }
}
