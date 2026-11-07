<?php

namespace Tests\Feature\Dragon;

use App\Models\Dragao;
use App\Models\Elemento;
use App\Models\EscolhaElemento;
use App\Models\Estagio;
use App\Models\Ovo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\VerificaCrudDaApi;
use Tests\TestCase;

class DragonApiTest extends TestCase
{
    use RefreshDatabase, VerificaCrudDaApi;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = User::factory()->create();
        Sanctum::actingAs($this->usuario);
    }

    private function dragaoDe(User $usuario): Dragao
    {
        return Dragao::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_ovo' => Ovo::create(['nome' => 'Ovo', 'imagem_url' => 'ovo.png'])->id,
            'fk_id_estagio' => Estagio::create(['nome' => 'Ovo', 'ordem' => 1, 'xp_marco' => 0])->id,
            'nome' => 'Smaug',
            'xp_ciclo' => 0,
            'status' => 'ATIVO',
            'data_nascimento' => '2026-11-01',
        ]);
    }

    public function test_crud_dos_catalogos_do_dragao(): void
    {
        $this->verificarCrud('/api/dragon/eggs', ['nome' => 'Ovo de fogo', 'imagem_url' => 'fogo.png'], ['nome' => 'Ovo flamejante']);
        $this->verificarCrud('/api/dragon/stages', ['nome' => 'Filhote', 'ordem' => 2, 'xp_marco' => 300], ['xp_marco' => 350]);
        $this->verificarCrud('/api/dragon/elements', ['nome' => 'Fogo', 'cenario_url' => 'vulcao.png'], ['cenario_url' => 'lava.png']);
    }

    public function test_cria_dragao_para_o_usuario_autenticado(): void
    {
        $ovo = Ovo::create(['nome' => 'Ovo', 'imagem_url' => 'ovo.png']);
        $estagio = Estagio::create(['nome' => 'Ovo', 'ordem' => 1, 'xp_marco' => 0]);

        $this->postJson('/api/dragon/dragons', [
            'fk_id_ovo' => $ovo->id,
            'fk_id_estagio' => $estagio->id,
            'nome' => 'Smaug',
            'xp_ciclo' => 0,
            'status' => 'ATIVO',
            'data_nascimento' => '2026-11-04',
        ])
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Smaug')
            ->assertJsonPath('data.status', 'ATIVO')
            ->assertJsonPath('data.data_nascimento', '2026-11-04')
            ->assertJsonPath('data.data_termino', null);

        $this->assertDatabaseHas('dragao', ['nome' => 'Smaug', 'fk_id_usuario' => $this->usuario->id]);
    }

    public function test_dragoes_de_outro_usuario_ficam_invisiveis(): void
    {
        $meu = $this->dragaoDe($this->usuario);
        $alheio = $this->dragaoDe(User::factory()->create());

        $this->getJson('/api/dragon/dragons')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $meu->id);
        $this->getJson("/api/dragon/dragons/{$alheio->id}")->assertNotFound();
        $this->patchJson("/api/dragon/dragons/{$alheio->id}", ['nome' => 'Invadido'])->assertNotFound();
        $this->deleteJson("/api/dragon/dragons/{$alheio->id}")->assertNotFound();
    }

    public function test_escolha_de_elemento_so_em_dragao_do_usuario(): void
    {
        $meu = $this->dragaoDe($this->usuario);
        $alheio = $this->dragaoDe(User::factory()->create());
        $elemento = Elemento::create(['nome' => 'Agua', 'cenario_url' => 'mar.png']);

        $corpo = [
            'fk_id_elemento' => $elemento->id,
            'fk_id_estagio' => $meu->fk_id_estagio,
            'data_escolha' => '2026-11-04 10:00:00',
        ];

        $this->postJson('/api/dragon/element-choices', [...$corpo, 'fk_id_dragao' => $alheio->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fk_id_dragao']);

        $this->postJson('/api/dragon/element-choices', [...$corpo, 'fk_id_dragao' => $meu->id])
            ->assertCreated()
            ->assertJsonPath('data.fk_id_dragao', $meu->id);
    }

    public function test_escolhas_de_elemento_de_outro_usuario_ficam_invisiveis(): void
    {
        $alheio = $this->dragaoDe(User::factory()->create());
        $escolha = EscolhaElemento::create([
            'fk_id_dragao' => $alheio->id,
            'fk_id_elemento' => Elemento::create(['nome' => 'Ar', 'cenario_url' => 'ceu.png'])->id,
            'fk_id_estagio' => $alheio->fk_id_estagio,
            'data_escolha' => '2026-11-04 10:00:00',
        ]);

        $this->getJson('/api/dragon/element-choices')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/dragon/element-choices/{$escolha->id}")->assertNotFound();
    }
}
