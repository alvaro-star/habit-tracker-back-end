<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Habits\HabitoDTO;
use App\Enums\DiaSemana;
use App\Enums\Frequencia;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class DiaFrequenciaTest extends TestCase
{
    public function test_cada_frequencia_aceita_apenas_o_seu_tipo_de_dia(): void
    {
        $this->assertTrue(Frequencia::DIARIA->aceitaDia(null));
        $this->assertFalse(Frequencia::DIARIA->aceitaDia('SEG'));

        $this->assertTrue(Frequencia::SEMANAL->aceitaDia('DOM'));
        $this->assertTrue(Frequencia::SEMANAL->aceitaDia(DiaSemana::SEX));
        $this->assertFalse(Frequencia::SEMANAL->aceitaDia('seg'));
        $this->assertFalse(Frequencia::SEMANAL->aceitaDia(null));

        $this->assertTrue(Frequencia::MENSAL->aceitaDia(1));
        $this->assertTrue(Frequencia::MENSAL->aceitaDia('31'));
        $this->assertFalse(Frequencia::MENSAL->aceitaDia(32));
        $this->assertFalse(Frequencia::MENSAL->aceitaDia('1.5'));
        $this->assertFalse(Frequencia::MENSAL->aceitaDia(true));
    }

    public function test_dia_da_semana_de_uma_data(): void
    {
        $this->assertSame(DiaSemana::SEG, DiaSemana::daData(CarbonImmutable::parse('2026-11-09')));
        $this->assertSame(DiaSemana::DOM, DiaSemana::daData(CarbonImmutable::parse('2026-11-08')));
    }

    public function test_dto_converte_o_dia_conforme_o_tipo(): void
    {
        $this->assertSame(15, HabitoDTO::fromArray(['dia_frequencia' => '15'])->diaFrequencia);
        $this->assertSame(DiaSemana::TER, HabitoDTO::fromArray(['dia_frequencia' => 'TER'])->diaFrequencia);
        $this->assertSame(['dia_frequencia' => 'TER'], HabitoDTO::fromArray(['dia_frequencia' => 'TER'])->toArray());
    }
}
