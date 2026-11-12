<?php

namespace Tests\Feature\Onboarding;

use App\Models\Categoria;
use App\Models\Dragao;
use App\Models\Habito;
use App\Models\HabitoModelo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OnboardingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Categoria $saude;

    private Categoria $estudos;

    private HabitoModelo $beberAgua;

    private HabitoModelo $estudar;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-12 10:00:00');

        $this->saude = Categoria::create(['nome' => 'Saúde']);
        $this->estudos = Categoria::create(['nome' => 'Estudos']);
        $this->beberAgua = HabitoModelo::create([
            'fk_id_categoria' => $this->saude->id,
            'nome' => 'Beber água',
            'tipo_meta' => 'QUANTIDADE',
            'meta_padrao' => 2,
            'unidade_padrao' => 'litros',
            'dificuldade_padrao' => 'FACIL',
            'prioridade_mvp' => true,
        ]);
        $this->estudar = HabitoModelo::create([
            'fk_id_categoria' => $this->estudos->id,
            'nome' => 'Estudar',
            'tipo_meta' => 'TEMPO',
            'meta_padrao' => 60,
            'unidade_padrao' => 'minutos',
            'dificuldade_padrao' => 'DIFICIL',
            'prioridade_mvp' => true,
        ]);

        $this->usuario = User::factory()->create(['name' => 'Nome do cadastro'])->refresh();
        Sanctum::actingAs($this->usuario);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function corpo(array $extra = []): array
    {
        return array_merge([
            'ovo' => 'ovo_azul',
            'nome_dragao' => 'Smaug',
            'nome_usuario' => 'João',
            'objetivos' => [$this->saude->id, $this->estudos->id],
            'habitos' => [$this->beberAgua->id, $this->estudar->id],
        ], $extra);
    }

    public function test_rotas_exigem_autenticacao(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/onboarding')->assertUnauthorized();
        $this->postJson('/api/onboarding', $this->corpo())->assertUnauthorized();
    }

    public function test_primeiro_acesso_tem_onboarding_pendente(): void
    {
        $this->getJson('/api/onboarding')
            ->assertOk()
            ->assertJsonPath('data.concluido', false)
            ->assertJsonPath('data.dragao', null)
            ->assertJsonCount(0, 'data.objetivos')
            ->assertJsonCount(0, 'data.habitos');

        $this->getJson('/api/user')->assertOk()->assertJsonPath('data.onboarding_concluido', false);
    }

    public function test_conclui_o_onboarding_com_ovo_dragao_objetivos_e_habitos(): void
    {
        $this->postJson('/api/onboarding', $this->corpo())
            ->assertCreated()
            ->assertJsonPath('data.concluido', true)
            ->assertJsonPath('data.nome_usuario', 'João')
            ->assertJsonPath('data.dragao.nome', 'Smaug')
            ->assertJsonPath('data.dragao.ovo', 'ovo_azul')
            ->assertJsonPath('data.dragao.status', 'ATIVO')
            ->assertJsonPath('data.dragao.estagio', 'OVO')
            ->assertJsonPath('data.dragao.xp_ciclo', 0)
            ->assertJsonPath('data.dragao.data_nascimento', '2026-11-12')
            ->assertJsonCount(2, 'data.objetivos')
            ->assertJsonCount(2, 'data.habitos');

        $usuario = $this->usuario->refresh();
        $this->assertTrue($usuario->onboarding_concluido);
        $this->assertSame('João', $usuario->name);
        $this->assertSame($usuario->fk_id_dragao, Dragao::where('fk_id_usuario', $usuario->id)->sole()->id);
        $this->assertEqualsCanonicalizing(
            [$this->saude->id, $this->estudos->id],
            $usuario->objetivos()->pluck('categoria.id')->all(),
        );

        // Cada modelo vira um hábito diário, com a meta padrão e o peso de referência da dificuldade.
        $this->assertDatabaseHas('habito', [
            'fk_id_usuario' => $usuario->id,
            'fk_id_categoria' => $this->saude->id,
            'nome' => 'Beber água',
            'polaridade' => 'BOM',
            'tipo_meta' => 'QUANTIDADE',
            'frequencia' => 'DIARIA',
            'dia_frequencia' => null,
            'valor_meta' => 2,
            'unidade' => 'litros',
            'dificuldade' => 'FACIL',
            'peso' => 5,
            'ativo' => true,
        ]);
        $this->assertDatabaseHas('habito', [
            'fk_id_usuario' => $usuario->id,
            'nome' => 'Estudar',
            'tipo_meta' => 'TEMPO',
            'valor_meta' => 60,
            'dificuldade' => 'DIFICIL',
            'peso' => 15,
        ]);

        $this->getJson('/api/user')->assertOk()->assertJsonPath('data.onboarding_concluido', true);
        $this->getJson('/api/onboarding')
            ->assertOk()
            ->assertJsonPath('data.concluido', true)
            ->assertJsonPath('data.dragao.ovo', 'ovo_azul');
        $this->getJson('/api/habits/daily?data=2026-11-12')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_aceita_onboarding_sem_objetivos_nem_habitos(): void
    {
        $this->postJson('/api/onboarding', $this->corpo(['objetivos' => [], 'habitos' => []]))
            ->assertCreated()
            ->assertJsonPath('data.concluido', true)
            ->assertJsonPath('data.dragao.ovo', 'ovo_azul')
            ->assertJsonCount(0, 'data.habitos');
    }

    public function test_onboarding_so_pode_ser_concluido_uma_vez(): void
    {
        $this->postJson('/api/onboarding', $this->corpo())->assertCreated();

        $this->postJson('/api/onboarding', $this->corpo(['nome_dragao' => 'Outro']))
            ->assertConflict()
            ->assertJsonValidationErrors(['onboarding']);

        $this->assertSame(1, Dragao::count());
        $this->assertSame(2, Habito::count());
    }

    public function test_valida_os_campos(): void
    {
        $this->postJson('/api/onboarding', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ovo', 'nome_dragao', 'nome_usuario', 'objetivos', 'habitos']);

        $this->postJson('/api/onboarding', $this->corpo([
            'ovo' => str_repeat('a', 41),
            'nome_dragao' => str_repeat('a', 41),
            'objetivos' => [999],
            'habitos' => [$this->beberAgua->id, $this->beberAgua->id, 999],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ovo', 'nome_dragao', 'objetivos.0', 'habitos.0', 'habitos.1', 'habitos.2']);

        $this->assertSame(0, Dragao::count());
        $this->assertFalse((bool) $this->usuario->refresh()->onboarding_concluido);
    }

    public function test_nada_e_gravado_se_o_usuario_ja_tem_dragao_ativo(): void
    {
        $this->postJson('/api/dragon/dragons', [
            'nome' => 'Antigo',
            'xp_ciclo' => 0,
            'status' => 'ATIVO',
            'data_nascimento' => '2026-11-01',
        ])->assertCreated();

        $this->postJson('/api/onboarding', $this->corpo())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $usuario = $this->usuario->refresh();
        $this->assertFalse($usuario->onboarding_concluido);
        $this->assertSame('Nome do cadastro', $usuario->name);
        $this->assertSame(0, Habito::count());
        $this->assertSame(0, $usuario->objetivos()->count());
    }

    public function test_onboarding_de_um_usuario_nao_afeta_outro(): void
    {
        $this->postJson('/api/onboarding', $this->corpo())->assertCreated();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/onboarding')
            ->assertOk()
            ->assertJsonPath('data.concluido', false)
            ->assertJsonPath('data.dragao', null)
            ->assertJsonCount(0, 'data.habitos');
        $this->postJson('/api/onboarding', $this->corpo(['ovo' => 'ovo_verde']))
            ->assertCreated()
            ->assertJsonPath('data.dragao.ovo', 'ovo_verde');
    }

    public function test_dragao_aceita_o_ovo_no_crud(): void
    {
        $id = $this->postJson('/api/dragon/dragons', [
            'nome' => 'Smaug',
            'ovo' => 'ovo_vermelho',
            'xp_ciclo' => 0,
            'status' => 'ATIVO',
            'data_nascimento' => '2026-11-12',
        ])->assertCreated()->assertJsonPath('data.ovo', 'ovo_vermelho')->json('data.id');

        $this->patchJson("/api/dragon/dragons/{$id}", ['ovo' => str_repeat('a', 41)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ovo']);
        $this->patchJson("/api/dragon/dragons/{$id}", ['ovo' => 'ovo_dourado'])
            ->assertOk()
            ->assertJsonPath('data.ovo', 'ovo_dourado');
    }

    public function test_migration_marca_como_concluido_quem_ja_tinha_dragao_ativo(): void
    {
        $migration = require database_path('migrations/2026_11_12_100000_adicionar_ovo_em_dragao.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('dragao', 'ovo'));

        $comDragao = User::factory()->create();
        $dragaoId = DB::table('dragao')->insertGetId([
            'fk_id_usuario' => $comDragao->id,
            'nome' => 'Antigo',
            'xp_ciclo' => 500,
            'status' => 'ATIVO',
            'data_nascimento' => '2026-11-01',
        ]);
        DB::table('users')->where('id', $comDragao->id)->update(['fk_id_dragao' => $dragaoId]);

        $migration->up();

        $this->assertDatabaseHas('users', ['id' => $comDragao->id, 'onboarding_concluido' => true]);
        $this->assertDatabaseHas('users', ['id' => $this->usuario->id, 'onboarding_concluido' => false]);
        $this->assertDatabaseHas('dragao', ['id' => $dragaoId, 'ovo' => null]);
    }
}
