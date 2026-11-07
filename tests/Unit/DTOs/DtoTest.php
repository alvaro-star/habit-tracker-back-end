<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Dragon\DragaoDTO;
use App\DTOs\Habits\HabitoDTO;
use App\Enums\Dificuldade;
use App\Enums\Polaridade;
use App\Enums\StatusDragao;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class DtoTest extends TestCase
{
    public function test_converte_os_dados_da_requisicao_para_tipos_php(): void
    {
        $dto = HabitoDTO::fromArray([
            'fk_id_categoria' => '3',
            'nome' => 'Ler',
            'polaridade' => 'BOM',
            'peso' => '12',
            'valor_meta' => '10.5',
            'ativo' => '0',
        ]);

        $this->assertSame(3, $dto->categoriaId);
        $this->assertSame(Polaridade::BOM, $dto->polaridade);
        $this->assertSame(12, $dto->peso);
        $this->assertSame(10.5, $dto->valorMeta);
        $this->assertFalse($dto->ativo);
    }

    public function test_to_array_devolve_as_colunas_e_descarta_os_campos_nao_informados(): void
    {
        $dto = HabitoDTO::fromArray(['nome' => 'Ler', 'polaridade' => 'RUIM', 'ativo' => false]);

        $this->assertSame(['nome' => 'Ler', 'polaridade' => 'RUIM', 'ativo' => false], $dto->toArray());
    }

    public function test_habito_dto_nao_aceita_dificuldade(): void
    {
        $dto = HabitoDTO::fromArray(['nome' => 'Ler', 'dificuldade' => 'FACIL']);

        $this->assertArrayNotHasKey('dificuldade', $dto->toArray());
    }

    public function test_dificuldade_vem_do_peso(): void
    {
        $this->assertSame(Dificuldade::FACIL, Dificuldade::doPeso(1));
        $this->assertSame(Dificuldade::MEDIO, Dificuldade::doPeso(6));
        $this->assertSame(Dificuldade::DIFICIL, Dificuldade::doPeso(15));
        $this->assertSame(Dificuldade::MUITO_DIFICIL, Dificuldade::doPeso(20));
    }

    public function test_peso_vem_da_dificuldade(): void
    {
        $this->assertSame(5, Dificuldade::FACIL->peso());
        $this->assertSame(10, Dificuldade::MEDIO->peso());
        $this->assertSame(15, Dificuldade::DIFICIL->peso());
        $this->assertSame(20, Dificuldade::MUITO_DIFICIL->peso());
    }

    public function test_datas_viram_carbon_e_voltam_como_texto(): void
    {
        $dto = DragaoDTO::fromArray(['data_nascimento' => '2026-11-04', 'status' => 'ATIVO']);

        $this->assertInstanceOf(CarbonImmutable::class, $dto->dataNascimento);
        $this->assertSame(StatusDragao::ATIVO, $dto->status);
        $this->assertSame(['status' => 'ATIVO', 'data_nascimento' => '2026-11-04'], $dto->toArray());
    }
}
