<?php

namespace Tests\Unit\DTOs;

use App\Enums\EstagioDragao;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EstagioDragaoTest extends TestCase
{
    /** @return array<string, array{0: int, 1: EstagioDragao}> */
    public static function xpEEstagio(): array
    {
        return [
            'sem XP' => [0, EstagioDragao::OVO],
            'um XP antes do filhote' => [119, EstagioDragao::OVO],
            'marco do filhote' => [120, EstagioDragao::FILHOTE],
            'marco do jovem' => [1000, EstagioDragao::JOVEM],
            'marco do adulto' => [3500, EstagioDragao::ADULTO],
            'escolha do elemento ainda é adulto' => [6000, EstagioDragao::ADULTO],
            'marco do adulto final' => [9000, EstagioDragao::ADULTO_FINAL],
            'acima do máximo' => [12000, EstagioDragao::ADULTO_FINAL],
        ];
    }

    #[DataProvider('xpEEstagio')]
    public function test_estagio_pelo_xp_do_ciclo(int $xp, EstagioDragao $estagio): void
    {
        $this->assertSame($estagio, EstagioDragao::doXp($xp));
    }

    public function test_escolha_de_elemento_e_aos_6000_xp(): void
    {
        $this->assertSame(6000, EstagioDragao::XP_ESCOLHA_ELEMENTO);
    }
}
