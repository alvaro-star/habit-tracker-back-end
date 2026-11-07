<?php

namespace Tests\Unit\Progression;

use Tests\TestCase;

class ConfiguracaoProgressaoTest extends TestCase
{
    public function test_teto_diario_de_xp_e_25(): void
    {
        $this->assertSame(25, config('progression.teto_diario'));
    }
}
