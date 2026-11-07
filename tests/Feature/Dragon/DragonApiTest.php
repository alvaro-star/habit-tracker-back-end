<?php

namespace Tests\Feature\Dragon;

use App\Models\Dragao;
use App\Models\EscolhaElemento;
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
            'nome' => 'Smaug',
            'xp_ciclo' => 0,
            'status' => 'ATIVO',
            'data_nascimento' => '2026-11-01',
        ]);
    }

    public function test_elementos_nao_tem_mais_endpoint(): void
    {
        $this->getJson('/api/dragon/elements')->assertNotFound();
    }

    public function test_ovos_e_estagios_nao_tem_mais_endpoints(): void
    {
        $this->getJson('/api/dragon/eggs')->assertNotFound();
        $this->getJson('/api/dragon/stages')->assertNotFound();
    }

    public function test_cria_dragao_para_o_usuario_autenticado(): void
    {
        $this->postJson('/api/dragon/dragons', [
            'nome' => 'Smaug',
            'xp_ciclo' => 0,
            'status' => 'ATIVO',
            'data_nascimento' => '2026-11-04',
        ])
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Smaug')
            ->assertJsonPath('data.status', 'ATIVO')
            ->assertJsonPath('data.estagio', 'OVO')
            ->assertJsonPath('data.data_nascimento', '2026-11-04')
            ->assertJsonPath('data.data_termino', null);

        $this->assertDatabaseHas('dragao', ['nome' => 'Smaug', 'fk_id_usuario' => $this->usuario->id]);
    }

    private function corpoDragao(array $extra = []): array
    {
        return array_merge([
            'nome' => 'Smaug',
            'xp_ciclo' => 0,
            'status' => 'ATIVO',
            'data_nascimento' => '2026-11-04',
        ], $extra);
    }

    public function test_criar_dragao_ativo_vira_o_dragao_do_usuario(): void
    {
        $id = $this->postJson('/api/dragon/dragons', $this->corpoDragao())->assertCreated()->json('data.id');

        $this->assertSame($id, $this->usuario->refresh()->fk_id_dragao);
        $this->getJson('/api/user')->assertOk()->assertJsonPath('data.fk_id_dragao', $id);
    }

    public function test_so_um_dragao_ativo_por_usuario(): void
    {
        $this->postJson('/api/dragon/dragons', $this->corpoDragao())->assertCreated();

        $this->postJson('/api/dragon/dragons', $this->corpoDragao(['nome' => 'Segundo']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        // Um dragão do Santuário pode ser criado mesmo com outro ativo.
        $this->postJson('/api/dragon/dragons', $this->corpoDragao(['nome' => 'Antigo', 'status' => 'SANTUARIO']))->assertCreated();
        $this->assertSame(2, Dragao::where('fk_id_usuario', $this->usuario->id)->count());
    }

    public function test_aposentar_e_reativar_atualiza_o_dragao_do_usuario(): void
    {
        $primeiro = $this->postJson('/api/dragon/dragons', $this->corpoDragao())->json('data.id');
        $segundo = $this->postJson('/api/dragon/dragons', $this->corpoDragao(['status' => 'SANTUARIO']))->json('data.id');

        $this->patchJson("/api/dragon/dragons/{$segundo}", ['status' => 'ATIVO'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->patchJson("/api/dragon/dragons/{$primeiro}", ['status' => 'SANTUARIO'])->assertOk();
        $this->assertNull($this->usuario->refresh()->fk_id_dragao);

        $this->patchJson("/api/dragon/dragons/{$segundo}", ['status' => 'ATIVO'])->assertOk();
        $this->assertSame($segundo, $this->usuario->refresh()->fk_id_dragao);
    }

    public function test_excluir_o_dragao_ativo_limpa_o_dragao_do_usuario(): void
    {
        $id = $this->postJson('/api/dragon/dragons', $this->corpoDragao())->json('data.id');

        $this->deleteJson("/api/dragon/dragons/{$id}")->assertNoContent();

        $this->assertNull($this->usuario->refresh()->fk_id_dragao);
    }

    public function test_estagio_vem_do_xp_do_ciclo_e_nao_pode_ser_enviado(): void
    {
        $id = $this->postJson('/api/dragon/dragons', $this->corpoDragao(['xp_ciclo' => 1500, 'estagio' => 'OVO']))
            ->assertCreated()
            ->assertJsonPath('data.estagio', 'JOVEM')
            ->json('data.id');

        $this->patchJson("/api/dragon/dragons/{$id}", ['xp_ciclo' => 9000, 'elemento' => 'FOGO'])
            ->assertOk()
            ->assertJsonPath('data.estagio', 'ADULTO_FINAL');

        $this->assertDatabaseHas('dragao', ['id' => $id, 'estagio' => 'ADULTO_FINAL']);
    }

    public function test_elemento_e_obrigatorio_a_partir_dos_6000_xp(): void
    {
        $id = $this->postJson('/api/dragon/dragons', $this->corpoDragao(['xp_ciclo' => 5000]))->json('data.id');

        // Antes dos 6.000 XP, o elemento é opcional.
        $this->patchJson("/api/dragon/dragons/{$id}", ['nome' => 'Smaug II'])->assertOk()
            ->assertJsonPath('data.precisa_escolher_elemento', false);

        // Chegar aos 6.000 XP sem elemento devolve 422.
        $this->patchJson("/api/dragon/dragons/{$id}", ['xp_ciclo' => 6000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['elemento']);

        // Com o elemento, passa; e depois disso ele não precisa ser reenviado.
        $this->patchJson("/api/dragon/dragons/{$id}", ['xp_ciclo' => 6000, 'elemento' => 'AGUA'])
            ->assertOk()
            ->assertJsonPath('data.elemento', 'AGUA')
            ->assertJsonPath('data.precisa_escolher_elemento', false);
        $this->patchJson("/api/dragon/dragons/{$id}", ['nome' => 'Smaug III'])->assertOk();

        // Um PATCH não limpa campos com null (o DTO descarta nulos): o elemento continua.
        $this->patchJson("/api/dragon/dragons/{$id}", ['elemento' => null])
            ->assertOk()
            ->assertJsonPath('data.elemento', 'AGUA');

        // Só valem FOGO, TERRA, AGUA e AR.
        $this->patchJson("/api/dragon/dragons/{$id}", ['elemento' => 'GELO'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['elemento']);
    }

    public function test_dragao_que_passou_dos_6000_xp_pede_o_elemento(): void
    {
        $id = $this->postJson('/api/dragon/dragons', $this->corpoDragao(['xp_ciclo' => 6100]))->json('data.id');

        $this->getJson("/api/dragon/dragons/{$id}")->assertOk()->assertJsonPath('data.precisa_escolher_elemento', true);
    }

    public function test_trocar_o_elemento_registra_a_trajetoria(): void
    {
        $id = $this->postJson('/api/dragon/dragons', $this->corpoDragao(['xp_ciclo' => 6000]))->json('data.id');

        $this->patchJson("/api/dragon/dragons/{$id}", ['elemento' => 'FOGO'])->assertOk();
        $this->patchJson("/api/dragon/dragons/{$id}", ['elemento' => 'FOGO'])->assertOk(); // mesmo elemento: não repete
        $this->patchJson("/api/dragon/dragons/{$id}", ['xp_ciclo' => 9000, 'elemento' => 'TERRA'])->assertOk();

        $trajetoria = EscolhaElemento::where('fk_id_dragao', $id)->orderBy('id')->get();
        $this->assertSame(['FOGO', 'TERRA'], $trajetoria->map(fn ($escolha) => $escolha->elemento->value)->all());
        $this->assertSame(['ADULTO', 'ADULTO_FINAL'], $trajetoria->map(fn ($escolha) => $escolha->estagio->value)->all());

        $this->getJson('/api/dragon/element-choices')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_escolha_de_elemento_guarda_o_estagio_atual_do_dragao(): void
    {
        $id = $this->postJson('/api/dragon/dragons', $this->corpoDragao(['xp_ciclo' => 6000]))->json('data.id');

        $this->postJson('/api/dragon/element-choices', [
            'fk_id_dragao' => $id,
            'elemento' => 'FOGO',
            'data_escolha' => '2026-11-04 10:00:00',
        ])->assertCreated()->assertJsonPath('data.estagio', 'ADULTO');
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

        $corpo = [
            'elemento' => 'FOGO',
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
            'elemento' => 'AR',
            'data_escolha' => '2026-11-04 10:00:00',
        ]);

        $this->getJson('/api/dragon/element-choices')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/dragon/element-choices/{$escolha->id}")->assertNotFound();
    }
}
