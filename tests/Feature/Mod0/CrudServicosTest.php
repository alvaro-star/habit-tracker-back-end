<?php

namespace Tests\Feature\Mod0;

use App\Enums\CanalNotificacao;
use App\Enums\Dificuldade;
use App\Enums\StatusDragao;
use App\Enums\TipoMeta;
use App\Models\Dragao;
use App\Models\Estagio;
use App\Models\User;
use App\Services\BaseService;
use App\Services\CategoriaService;
use App\Services\DragaoService;
use App\Services\ElementoService;
use App\Services\EscolhaElementoService;
use App\Services\EstagioService;
use App\Services\GatilhoNotificacaoService;
use App\Services\HabitoModeloService;
use App\Services\HabitoService;
use App\Services\InsigniaService;
use App\Services\NivelService;
use App\Services\OvoService;
use App\Services\RegistroHabitoService;
use App\Services\XpDiarioService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudServicosTest extends TestCase
{
    use RefreshDatabase;

    public function test_nivel_crud(): void
    {
        $this->verificarCicloCrud(app(NivelService::class), [
            'xp_necessario' => 100,
            'xp_acumulado' => 100,
        ], ['xp_acumulado' => 250], 'xp_acumulado');
    }

    public function test_estagio_crud(): void
    {
        $this->verificarCicloCrud(app(EstagioService::class), [
            'nome' => 'Ovo',
            'ordem' => 1,
            'xp_marco' => 0,
        ], ['nome' => 'Filhote'], 'nome');
    }

    public function test_elemento_crud(): void
    {
        $this->verificarCicloCrud(app(ElementoService::class), [
            'nome' => 'Fogo',
            'cenario_url' => 'https://exemplo.test/fogo.png',
        ], ['cenario_url' => 'https://exemplo.test/fogo-2.png'], 'cenario_url');
    }

    public function test_ovo_crud(): void
    {
        $this->verificarCicloCrud(app(OvoService::class), [
            'nome' => 'Ovo Verde',
            'imagem_url' => 'https://exemplo.test/ovo.png',
        ], ['nome' => 'Ovo Dourado'], 'nome');
    }

    public function test_insignia_crud(): void
    {
        $this->verificarCicloCrud(app(InsigniaService::class), [
            'nome' => 'Primeira semana',
            'descricao' => 'Sete dias seguidos de check-in',
            'icone_url' => 'https://exemplo.test/insignia.png',
        ], ['descricao' => 'Quatorze dias seguidos'], 'descricao');
    }

    public function test_gatilho_notificacao_crud(): void
    {
        $this->verificarCicloCrud(app(GatilhoNotificacaoService::class), [
            'tipo' => 'LEMBRETE',
            'canal' => CanalNotificacao::PUSH,
            'frequencia' => 'DIARIA',
            'texto_base' => 'Hora do seu hábito',
        ], ['canal' => CanalNotificacao::IN_APP], 'canal');
    }

    public function test_categoria_crud(): void
    {
        $this->verificarCicloCrud(app(CategoriaService::class), [
            'nome' => 'Saúde',
        ], ['nome' => 'Estudos'], 'nome');
    }

    public function test_habito_modelo_crud(): void
    {
        $categoria = app(CategoriaService::class)->criar(['nome' => 'Saúde']);

        $this->verificarCicloCrud(app(HabitoModeloService::class), [
            'fk_id_categoria' => $categoria->id,
            'nome' => 'Beber água',
            'tipo_meta' => TipoMeta::QUANTIDADE,
            'meta_padrao' => 2.5,
            'unidade_padrao' => 'L',
            'dificuldade_padrao' => Dificuldade::FACIL,
            'prioridade_mvp' => true,
        ], ['nome' => 'Beber água diariamente'], 'nome');
    }

    public function test_dragao_crud(): void
    {
        $usuario = User::factory()->create();
        $ovo = app(OvoService::class)->criar([
            'nome' => 'Ovo Verde',
            'imagem_url' => 'https://exemplo.test/ovo.png',
        ]);
        $estagio = app(EstagioService::class)->criar(['nome' => 'Ovo', 'ordem' => 1, 'xp_marco' => 0]);

        $this->verificarCicloCrud(app(DragaoService::class), [
            'fk_id_usuario' => $usuario->id,
            'fk_id_ovo' => $ovo->id,
            'fk_id_estagio' => $estagio->id,
            'nome' => 'Ignis',
            'xp_ciclo' => 0,
            'status' => StatusDragao::ATIVO,
            'data_nascimento' => '2026-11-01',
        ], ['status' => StatusDragao::SANTUARIO], 'status');
    }

    public function test_escolha_elemento_crud(): void
    {
        [$dragao, $estagio] = $this->criarDragao();
        $elemento = app(ElementoService::class)->criar([
            'nome' => 'Água',
            'cenario_url' => 'https://exemplo.test/agua.png',
        ]);
        $outroEstagio = app(EstagioService::class)->criar(['nome' => 'Filhote', 'ordem' => 2, 'xp_marco' => 500]);

        $this->verificarCicloCrud(app(EscolhaElementoService::class), [
            'fk_id_dragao' => $dragao->id,
            'fk_id_elemento' => $elemento->id,
            'fk_id_estagio' => $estagio->id,
            'data_escolha' => now(),
        ], ['fk_id_estagio' => $outroEstagio->id], 'fk_id_estagio');
    }

    public function test_xp_diario_crud(): void
    {
        [$dragao] = $this->criarDragao();

        $this->verificarCicloCrud(app(XpDiarioService::class), [
            'fk_id_usuario' => $dragao->fk_id_usuario,
            'fk_id_dragao' => $dragao->id,
            'data' => '2026-11-04',
            'percentual_ponderado' => 87.5,
            'xp_ganho' => 40,
        ], ['xp_ganho' => 55], 'xp_ganho');
    }

    public function test_habito_crud(): void
    {
        $usuario = User::factory()->create();
        $categoria = app(CategoriaService::class)->criar(['nome' => 'Estudos']);

        $this->verificarCicloCrud(app(HabitoService::class), [
            'fk_id_usuario' => $usuario->id,
            'fk_id_categoria' => $categoria->id,
            'nome' => 'Ler',
            'polaridade' => 'BOM',
            'tipo_meta' => 'REALIZACAO',
            'frequencia' => 'DIARIA',
            'peso' => 5,
            'ativo' => true,
        ], ['nome' => 'Ler dez páginas'], 'nome');
    }

    public function test_registro_habito_crud(): void
    {
        $usuario = User::factory()->create();
        $categoria = app(CategoriaService::class)->criar(['nome' => 'Estudos']);
        $habito = app(HabitoService::class)->criar([
            'fk_id_usuario' => $usuario->id,
            'fk_id_categoria' => $categoria->id,
            'nome' => 'Ler',
            'polaridade' => 'BOM',
            'tipo_meta' => 'REALIZACAO',
            'frequencia' => 'DIARIA',
            'peso' => 5,
            'ativo' => true,
        ]);

        $this->verificarCicloCrud(app(RegistroHabitoService::class), [
            'fk_id_habito' => $habito->id,
            'data' => '2026-11-04',
            'status' => 'CONCLUIDO',
            'percentual' => 100,
        ], ['status' => 'EM_ANDAMENTO'], 'status');
    }

    public function test_atualizar_ou_buscar_registro_inexistente_lanca_excecao(): void
    {
        $this->expectException(ModelNotFoundException::class);

        app(NivelService::class)->atualizar(999, ['xp_acumulado' => 1]);
    }

    /**
     * Cria um dragão com as dependências mínimas (usuário, ovo e estágio).
     *
     * @return array{0: Dragao, 1: Estagio}
     */
    private function criarDragao(): array
    {
        $usuario = User::factory()->create();
        $ovo = app(OvoService::class)->criar([
            'nome' => 'Ovo Verde',
            'imagem_url' => 'https://exemplo.test/ovo.png',
        ]);
        $estagio = app(EstagioService::class)->criar(['nome' => 'Ovo', 'ordem' => 1, 'xp_marco' => 0]);

        $dragao = app(DragaoService::class)->criar([
            'fk_id_usuario' => $usuario->id,
            'fk_id_ovo' => $ovo->id,
            'fk_id_estagio' => $estagio->id,
            'nome' => 'Ignis',
            'xp_ciclo' => 0,
            'status' => StatusDragao::ATIVO,
            'data_nascimento' => '2026-11-01',
        ]);

        return [$dragao, $estagio];
    }

    /**
     * Percorre criar → listar → buscar → atualizar → excluir e confirma cada etapa.
     *
     * @param  array<string, mixed>  $dados
     * @param  array<string, mixed>  $alteracoes
     */
    private function verificarCicloCrud(BaseService $servico, array $dados, array $alteracoes, string $campo): void
    {
        $criado = $servico->criar($dados);
        $id = $criado->getKey();

        $this->assertTrue($servico->listar()->contains('id', $id));
        $this->assertSame($id, $servico->buscar($id)->getKey());

        $atualizado = $servico->atualizar($id, $alteracoes);
        $this->assertEquals($alteracoes[$campo], $atualizado->getAttribute($campo));

        $this->assertTrue($servico->excluir($id));
        $this->assertFalse($servico->listar()->contains('id', $id));
    }
}
