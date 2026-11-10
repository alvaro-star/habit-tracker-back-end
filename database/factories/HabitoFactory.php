<?php

namespace Database\Factories;

use App\Enums\DiaSemana;
use App\Enums\Dificuldade;
use App\Enums\Frequencia;
use App\Enums\Polaridade;
use App\Enums\TipoMeta;
use App\Models\Categoria;
use App\Models\Habito;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Hábito diário de realização, ativo e bom, com a dificuldade coerente com o
 * peso (Dificuldade::doPeso). Os states cobrem os outros tipos de meta, as
 * frequências, a polaridade ruim e a desativação.
 *
 * A factory grava direto no banco, sem passar pelo HabitoService: não valida
 * as regras de negócio nem recalcula o XP do dia.
 *
 * @extends Factory<Habito>
 */
class HabitoFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $peso = fake()->numberBetween(Dificuldade::PESO_MINIMO, Dificuldade::PESO_MAXIMO);

        return [
            'fk_id_usuario' => User::factory(),
            'fk_id_categoria' => Categoria::factory(),
            'nome' => fake()->words(3, true),
            'polaridade' => Polaridade::BOM->value,
            'tipo_meta' => TipoMeta::REALIZACAO->value,
            'frequencia' => Frequencia::DIARIA->value,
            'dia_frequencia' => null,
            'valor_meta' => null,
            'unidade' => null,
            'peso' => $peso,
            'dificuldade' => Dificuldade::doPeso($peso)->value,
            'ativo' => true,
        ];
    }

    /** Hábito do usuário informado. */
    public function doUsuario(User $usuario): static
    {
        return $this->state(fn () => ['fk_id_usuario' => $usuario->id]);
    }

    /** Peso fixo, com a dificuldade gerada por ele. */
    public function comPeso(int $peso): static
    {
        return $this->state(fn () => [
            'peso' => $peso,
            'dificuldade' => Dificuldade::doPeso($peso)->value,
        ]);
    }

    public function ruim(): static
    {
        return $this->state(fn () => ['polaridade' => Polaridade::RUIM->value]);
    }

    /** Meta de tempo, em minutos. */
    public function deTempo(float $minutos = 30): static
    {
        return $this->state(fn () => [
            'tipo_meta' => TipoMeta::TEMPO->value,
            'valor_meta' => $minutos,
            'unidade' => null,
        ]);
    }

    /** Meta de quantidade, com unidade customizada. */
    public function deQuantidade(float $valor = 2, string $unidade = 'litros'): static
    {
        return $this->state(fn () => [
            'tipo_meta' => TipoMeta::QUANTIDADE->value,
            'valor_meta' => $valor,
            'unidade' => $unidade,
        ]);
    }

    public function semanal(DiaSemana $dia = DiaSemana::SEG): static
    {
        return $this->state(fn () => [
            'frequencia' => Frequencia::SEMANAL->value,
            'dia_frequencia' => $dia->value,
        ]);
    }

    /** O dia do mês é guardado como texto (habito.dia_frequencia). */
    public function mensal(int $dia = 1): static
    {
        return $this->state(fn () => [
            'frequencia' => Frequencia::MENSAL->value,
            'dia_frequencia' => (string) $dia,
        ]);
    }

    /** Desativado como o HabitoService faz: ativo = false e inactivated_at preenchido. */
    public function inativo(): static
    {
        return $this->state(fn () => [
            'ativo' => false,
            'inactivated_at' => now(),
        ]);
    }

    /**
     * Data de criação informada (ex.: no passado). A factory grava sem o
     * Fillable, e o Eloquent não sobrescreve um created_at já preenchido.
     */
    public function criadoEm(\DateTimeInterface|string $data): static
    {
        return $this->state(fn () => ['created_at' => $data]);
    }
}
