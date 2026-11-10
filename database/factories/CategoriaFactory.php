<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    /**
     * O nome da categoria é único (varchar 40), então o sufixo evita colisões
     * quando um teste cria várias categorias.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->bothify('Categoria ####??'),
        ];
    }
}
