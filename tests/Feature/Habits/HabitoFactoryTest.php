<?php

namespace Tests\Feature\Habits;

use App\Enums\DiaSemana;
use App\Enums\Dificuldade;
use App\Models\Categoria;
use App\Models\Habito;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HabitoFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_habito_diario_de_realizacao_com_usuario_e_categoria(): void
    {
        $habito = Habito::factory()->create();

        $this->assertInstanceOf(User::class, User::find($habito->fk_id_usuario));
        $this->assertInstanceOf(Categoria::class, $habito->categoria);
        $this->assertSame('REALIZACAO', $habito->tipo_meta);
        $this->assertSame('DIARIA', $habito->frequencia);
        $this->assertNull($habito->dia_frequencia);
        $this->assertTrue($habito->ativo);
        $this->assertNotNull($habito->created_at);
    }

    public function test_dificuldade_acompanha_o_peso(): void
    {
        foreach (Habito::factory()->count(10)->create() as $habito) {
            $this->assertSame(Dificuldade::doPeso($habito->peso)->value, $habito->dificuldade);
        }

        $this->assertSame('MUITO_DIFICIL', Habito::factory()->comPeso(18)->create()->dificuldade);
    }

    public function test_states_de_tipo_de_meta_e_frequencia(): void
    {
        $quantidade = Habito::factory()->deQuantidade(8, 'copos')->create();
        $this->assertSame('QUANTIDADE', $quantidade->tipo_meta);
        $this->assertSame('copos', $quantidade->unidade);
        $this->assertEquals(8, $quantidade->valor_meta);

        $this->assertSame('TEMPO', Habito::factory()->deTempo()->create()->tipo_meta);

        $semanal = Habito::factory()->semanal(DiaSemana::QUA)->create();
        $this->assertSame(['SEMANAL', 'QUA'], [$semanal->frequencia, $semanal->dia_frequencia]);

        $mensal = Habito::factory()->mensal(15)->create();
        $this->assertSame(['MENSAL', '15'], [$mensal->frequencia, $mensal->dia_frequencia]);
    }

    public function test_states_de_dono_polaridade_inativo_e_data_de_criacao(): void
    {
        $usuario = User::factory()->create();
        $habito = Habito::factory()->doUsuario($usuario)->ruim()->inativo()->criadoEm('2026-01-10 08:00:00')->create();

        $this->assertSame($usuario->id, $habito->fk_id_usuario);
        $this->assertSame('RUIM', $habito->polaridade);
        $this->assertFalse($habito->ativo);
        $this->assertNotNull($habito->inactivated_at);
        $this->assertSame('2026-01-10', $habito->fresh()->created_at->toDateString());
    }
}
