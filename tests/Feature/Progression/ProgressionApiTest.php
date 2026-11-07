<?php

namespace Tests\Feature\Progression;

use App\Models\Dragao;
use App\Models\Estagio;
use App\Models\Ovo;
use App\Models\User;
use App\Models\XpDiario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\VerificaCrudDaApi;
use Tests\TestCase;

class ProgressionApiTest extends TestCase
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

    public function test_crud_de_insignias(): void
    {
        $this->verificarCrud('/api/progression/badges', [
            'nome' => 'Primeiro passo',
            'descricao' => 'Concluiu o primeiro hábito',
            'icone_url' => 'passo.png',
        ], ['descricao' => 'Concluiu o primeiro hábito do dia']);
    }

    private function xpRegistrado(User $usuario, string $data, int $xp): XpDiario
    {
        return XpDiario::create([
            'fk_id_usuario' => $usuario->id,
            'fk_id_dragao' => $this->dragaoDe($usuario)->id,
            'data' => $data,
            'percentual_ponderado' => $xp * 4,
            'xp_ganho' => $xp,
        ]);
    }

    public function test_lista_o_xp_registrado_no_intervalo_inclusive_hoje(): void
    {
        $this->travelTo('2026-11-09 10:00:00');
        $this->xpRegistrado($this->usuario, '2026-10-31', 5); // antes do intervalo
        $inicio = $this->xpRegistrado($this->usuario, '2026-11-01', 10);
        $ontem = $this->xpRegistrado($this->usuario, '2026-11-08', 20);
        $hoje = $this->xpRegistrado($this->usuario, '2026-11-09', 25);

        $this->getJson('/api/progression/daily-xp?data_inicio=2026-11-01&data_fim=2026-11-30')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $hoje->id)
            ->assertJsonPath('data.0.data', '2026-11-09')
            ->assertJsonPath('data.1.id', $ontem->id)
            ->assertJsonPath('data.2.id', $inicio->id)
            ->assertJsonMissingPath('data.0.parcial');
    }

    public function test_intervalo_no_passado_traz_so_os_dias_do_intervalo(): void
    {
        $this->travelTo('2026-11-09 10:00:00');
        $xp = $this->xpRegistrado($this->usuario, '2026-11-02', 15);
        $this->xpRegistrado($this->usuario, '2026-11-09', 25);

        $this->getJson('/api/progression/daily-xp?data_inicio=2026-11-01&data_fim=2026-11-05')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $xp->id);
    }

    public function test_dia_sem_registro_nao_aparece(): void
    {
        $this->travelTo('2026-11-09 10:00:00');

        // Nada foi marcado nem criado hoje: não há registro, e nada é calculado na listagem.
        $this->getJson('/api/progression/daily-xp?data_inicio=2026-11-09&data_fim=2026-11-09')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->assertDatabaseCount('xp_diario', 0);
    }

    /** @return array<string, array{0: string, 1: array<int, string>}> */
    public static function intervalosInvalidos(): array
    {
        return [
            'sem intervalo' => ['', ['data_inicio', 'data_fim']],
            'sem data_fim' => ['data_inicio=2026-11-01', ['data_fim']],
            'dia inexistente' => ['data_inicio=2026-04-31&data_fim=2026-05-10', ['data_inicio']],
            'fim antes do início' => ['data_inicio=2026-11-10&data_fim=2026-11-01', ['data_fim']],
            'formato brasileiro' => ['data_inicio=01/11/2026&data_fim=2026-11-10', ['data_inicio']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('intervalosInvalidos')]
    public function test_recusa_intervalo_invalido(string $consulta, array $campos): void
    {
        $this->getJson("/api/progression/daily-xp?{$consulta}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors($campos);
    }

    public function test_xp_diario_de_outro_usuario_fica_invisivel(): void
    {
        $this->travelTo('2026-11-09 10:00:00');
        $this->xpRegistrado(User::factory()->create(), '2026-11-04', 12);

        $this->getJson('/api/progression/daily-xp?data_inicio=2026-11-01&data_fim=2026-11-08')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_xp_diario_nao_pode_ser_criado_editado_nem_excluido(): void
    {
        $xp = $this->xpRegistrado($this->usuario, '2026-11-04', 12);

        $this->postJson('/api/progression/daily-xp', ['xp_ganho' => 25])->assertMethodNotAllowed();
        $this->getJson("/api/progression/daily-xp/{$xp->id}")->assertNotFound();
        $this->putJson("/api/progression/daily-xp/{$xp->id}", ['xp_ganho' => 25])->assertNotFound();
        $this->deleteJson("/api/progression/daily-xp/{$xp->id}")->assertNotFound();

        $this->assertDatabaseHas('xp_diario', ['id' => $xp->id, 'xp_ganho' => 12]);
    }
}
