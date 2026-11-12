<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Dragon\DragaoDTO;
use App\DTOs\Onboarding\OnboardingDTO;
use PHPUnit\Framework\TestCase;

class OnboardingDtoTest extends TestCase
{
    public function test_converte_os_ids_para_inteiros_sem_repeticao(): void
    {
        $dto = OnboardingDTO::fromArray([
            'ovo' => 'ovo_azul',
            'nome_dragao' => 'Smaug',
            'nome_usuario' => 'João',
            'objetivos' => ['1', 2, '2'],
            'habitos' => ['7'],
        ]);

        $this->assertSame('ovo_azul', $dto->ovo);
        $this->assertSame('Smaug', $dto->nomeDragao);
        $this->assertSame('João', $dto->nomeUsuario);
        $this->assertSame([1, 2], $dto->objetivos);
        $this->assertSame([7], $dto->habitos);
    }

    public function test_dragao_dto_leva_o_ovo_para_a_coluna(): void
    {
        $this->assertSame(['ovo' => 'ovo_azul'], DragaoDTO::fromArray(['ovo' => 'ovo_azul'])->toArray());
    }
}
