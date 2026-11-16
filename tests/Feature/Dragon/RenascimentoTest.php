<?php

namespace Tests\Feature\Dragon;

use App\Models\Categoria;
use App\Models\Dragao;
use App\Models\EscolhaElemento;
use App\Models\Habito;
use App\Models\Insignia;
use App\Models\User;
use App\Models\UsuarioInsignia;
use App\Services\DragaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Renascimento (MOD6-TASK12 a TASK17). Cenário: hoje é 2026-11-15, o dragão
 * nasceu em 2026-01-01 e um hábito de peso 10 concluído rende 25 XP no dia.
 */
class RenascimentoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Habito $habito;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-10 09:00:00');
        $this->usuario = User::factory()->create();
        $this->habito = Habito::factory()->doUsuario($this->usuario)->comPeso(10)->create();
        $this->travelTo('2026-11-15 10:00:00');
        Sanctum::actingAs($this->usuario);
    }

    private function dragao(int $xpCiclo, ?string $elemento = 'FOGO'): Dragao
    {
        $dragao = Dragao::create([
            'fk_id_usuario' => $this->usuario->id,
            'nome' => 'Smaug',
            'ovo' => 'ovo-vulcao',
            'xp_ciclo' => $xpCiclo,
            'estagio' => $xpCiclo >= 9000 ? 'ADULTO_FINAL' : 'ADULTO',
            'elemento' => $elemento,
            'status' => 'ATIVO',
            'data_nascimento' => '2026-01-01',
        ]);
        $this->usuario->forceFill(['fk_id_dragao' => $dragao->id])->save();

        if ($elemento !== null) {
            EscolhaElemento::create([
                'fk_id_dragao' => $dragao->id,
                'elemento' => $elemento,
                'estagio' => 'ADULTO',
                'data_escolha' => '2026-09-01',
            ]);
        }

        return $dragao;
    }

    private function concluirHabitoHoje(): void
    {
        $this->postJson('/api/check-ins', [
            'fk_id_habito' => $this->habito->id,
            'data' => '2026-11-15',
            'checked' => true,
            'status' => 'CONCLUIDO',
        ])->assertSuccessful();
    }

    public function test_ao_chegar_aos_9000_xp_o_dragao_vai_ao_santuario_e_nasce_um_novo_ovo(): void
    {
        $antigo = $this->dragao(8990);

        $this->concluirHabitoHoje();

        // TASK17: o dragão concluído vai para o Santuário, com término e tempo total.
        $antigo->refresh();
        $this->assertSame('SANTUARIO', $antigo->status->value);
        $this->assertSame('ADULTO_FINAL', $antigo->estagio->value);
        $this->assertSame('2026-11-15', $antigo->data_termino->toDateString());
        $this->assertSame(318, $antigo->tempo_total_dias);

        // TASK12/13/14: um novo ovo ativo, com o XP do ciclo zerado.
        $usuario = $this->usuario->refresh();
        $novo = Dragao::findOrFail($usuario->fk_id_dragao);
        $this->assertNotSame($antigo->id, $novo->id);
        $this->assertSame('ATIVO', $novo->status->value);
        $this->assertSame('OVO', $novo->estagio->value);
        $this->assertSame(0, $novo->xp_ciclo);
        $this->assertSame(DragaoService::NOME_NOVO_OVO, $novo->nome);
        $this->assertNull($novo->elemento);
        $this->assertSame('2026-11-15', $novo->data_nascimento->toDateString());

        $this->getJson('/api/sanctuary')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $antigo->id)
            ->assertJsonPath('data.0.trajetoria.0.elemento', 'FOGO');
    }

    public function test_renascimento_preserva_o_nivel_e_as_insignias(): void
    {
        $this->dragao(8990);
        $this->usuario->forceFill(['xp_vitalicio' => 8990])->save();
        $insignia = Insignia::create(['nome' => 'Primeiro ciclo', 'descricao' => 'Concluiu um ciclo', 'icone_url' => 'https://exemplo.com/ciclo.png']);
        UsuarioInsignia::create(['fk_id_usuario' => $this->usuario->id, 'fk_id_insignia' => $insignia->id, 'data_conquista' => '2026-10-01']);
        $nivelAntes = $this->usuario->refresh()->getNivel();

        $this->concluirHabitoHoje();

        // TASK15: o XP vitalício continua somando (não é zerado), então o nível não cai.
        $usuario = $this->usuario->refresh();
        $this->assertSame(9015, $usuario->xp_vitalicio);
        $this->assertGreaterThanOrEqual($nivelAntes, $usuario->getNivel());
        // TASK16: as insígnias continuam com o usuário.
        $this->assertDatabaseHas('usuario_insignia', ['fk_id_usuario' => $usuario->id, 'fk_id_insignia' => $insignia->id]);
    }

    public function test_abaixo_dos_9000_xp_nada_muda(): void
    {
        $dragao = $this->dragao(8000);

        $this->concluirHabitoHoje();

        $this->assertSame('ATIVO', $dragao->refresh()->status->value);
        $this->assertSame($dragao->id, $this->usuario->refresh()->fk_id_dragao);
        $this->assertSame(1, Dragao::count());
    }

    public function test_sem_elemento_o_renascimento_espera_a_escolha(): void
    {
        // Dragão que passou dos 9.000 XP sem escolher o elemento (ex.: dados antigos).
        $dragao = $this->dragao(9100, null);

        $this->concluirHabitoHoje();

        $this->assertSame('ATIVO', $dragao->refresh()->status->value);
        $this->assertSame(1, Dragao::count());

        // Ao escolher o elemento, o ciclo se conclui.
        $this->patchJson("/api/dragon/dragons/{$dragao->id}", ['elemento' => 'AGUA'])
            ->assertOk()
            ->assertJsonPath('data.status', 'SANTUARIO');

        $novo = Dragao::findOrFail($this->usuario->refresh()->fk_id_dragao);
        $this->assertSame('OVO', $novo->estagio->value);
        $this->assertSame(0, $novo->xp_ciclo);
    }

    public function test_dragao_do_santuario_nao_renasce_de_novo(): void
    {
        $antigo = $this->dragao(8990);
        $this->concluirHabitoHoje();

        $this->assertNull(app(DragaoService::class)->renascerSeConcluiuCiclo($antigo->id));
        $this->assertSame(2, Dragao::count());
    }

    public function test_depois_do_renascimento_desmarcar_o_check_in_de_hoje_e_bloqueado(): void
    {
        $this->dragao(8990);
        $this->concluirHabitoHoje();
        $novoOvo = Dragao::findOrFail($this->usuario->fresh()->fk_id_dragao);
        $xpVitalicio = $this->usuario->fresh()->xp_vitalicio;
        // Numa requisição real o usuário é recarregado; no teste, o actingAs guarda o objeto antigo.
        Sanctum::actingAs($this->usuario->fresh());

        $this->postJson('/api/check-ins', [
            'fk_id_habito' => $this->habito->id,
            'data' => '2026-11-15',
            'checked' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('xp_ciclo');

        $this->assertSame(0, $novoOvo->fresh()->xp_ciclo);
        $this->assertSame($xpVitalicio, $this->usuario->fresh()->xp_vitalicio);
        $this->assertDatabaseHas('registro_habito', ['fk_id_habito' => $this->habito->id, 'status' => 'CONCLUIDO']);
    }

    public function test_depois_do_renascimento_criar_habito_que_cai_hoje_e_bloqueado(): void
    {
        $this->dragao(8990);
        $this->concluirHabitoHoje();
        $habito = [
            'fk_id_categoria' => Categoria::create(['nome' => 'Saúde'])->id,
            'nome' => 'Ler',
            'polaridade' => 'BOM',
            'tipo_meta' => 'REALIZACAO',
            'peso' => 10,
        ];

        // 2026-11-15 é domingo: um hábito diário cai hoje e baixaria o XP do novo ovo.
        $this->postJson('/api/habits', [...$habito, 'frequencia' => 'DIARIA', 'dia_frequencia' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('xp_ciclo');
        $this->assertDatabaseMissing('habito', ['nome' => 'Ler']);

        // Um semanal de segunda-feira não cai hoje, então não muda o XP do dia.
        $this->postJson('/api/habits', [...$habito, 'frequencia' => 'SEMANAL', 'dia_frequencia' => 'SEG'])
            ->assertCreated();
    }
}
