<?php

namespace Database\Seeders;

use App\Enums\Dificuldade;
use App\Enums\TipoMeta;
use App\Models\Categoria;
use App\Models\HabitoModelo;
use Illuminate\Database\Seeder;

/**
 * Biblioteca inicial de hábitos (MOD2-TASK1): as categorias e os modelos da
 * seção 6.1 do docs/ESCOPO.md. Quando o escopo dá duas opções de tipo de meta
 * ("Tempo / Realização"), fica a primeira. Pode rodar mais de uma vez.
 */
class BibliotecaSeeder extends Seeder
{
    /** As categorias do cadastro de hábitos (MOD3-TASK6), na ordem do escopo. */
    public const CATEGORIAS = ['Saúde', 'Estudos', 'Produtividade', 'Finanças', 'Desenvolvimento Pessoal', 'Outro'];

    public function run(): void
    {
        foreach (self::CATEGORIAS as $nome) {
            Categoria::firstOrCreate(['nome' => $nome]);
        }

        // [categoria, hábito, tipo de meta, meta padrão, unidade, dificuldade, prioridade do MVP]
        $modelos = [
            ['Saúde', 'Beber água', TipoMeta::QUANTIDADE, 2, 'litros', Dificuldade::FACIL, true],
            ['Saúde', 'Caminhar / Fazer exercícios', TipoMeta::TEMPO, 30, 'minutos', Dificuldade::MEDIO, true],
            ['Saúde', 'Dormir no horário', TipoMeta::REALIZACAO, 1, 'check-in', Dificuldade::FACIL, true],
            ['Saúde', 'Meditar / Alongamento / Vitaminas', TipoMeta::TEMPO, 15, 'minutos', Dificuldade::FACIL, false],
            ['Estudos', 'Estudar', TipoMeta::TEMPO, 60, 'minutos', Dificuldade::DIFICIL, true],
            ['Estudos', 'Ler', TipoMeta::TEMPO, 30, 'minutos', Dificuldade::MEDIO, true],
            ['Estudos', 'Revisar conteúdo / Fazer exercícios', TipoMeta::TEMPO, 30, 'minutos', Dificuldade::MEDIO, true],
            ['Produtividade', 'Planejar o dia / Organizar tarefas', TipoMeta::REALIZACAO, 1, 'check-in', Dificuldade::FACIL, true],
            ['Produtividade', 'Trabalhar / Fazer lista de tarefas', TipoMeta::TEMPO, 60, 'minutos', Dificuldade::MEDIO, false],
            ['Finanças', 'Registrar gastos / Conferir orçamento', TipoMeta::REALIZACAO, 1, 'check-in', Dificuldade::FACIL, true],
            ['Finanças', 'Economizar dinheiro', TipoMeta::QUANTIDADE, 10, 'R$', Dificuldade::MEDIO, true],
            ['Desenvolvimento Pessoal', 'Escrever no diário / Ler', TipoMeta::TEMPO, 20, 'minutos', Dificuldade::FACIL, true],
            ['Desenvolvimento Pessoal', 'Praticar hobby / Aprender algo novo', TipoMeta::TEMPO, 30, 'minutos', Dificuldade::MEDIO, true],
        ];

        $categorias = Categoria::query()->pluck('id', 'nome');

        foreach ($modelos as [$categoria, $nome, $tipoMeta, $meta, $unidade, $dificuldade, $prioridade]) {
            HabitoModelo::updateOrCreate(
                ['fk_id_categoria' => $categorias[$categoria], 'nome' => $nome],
                [
                    'tipo_meta' => $tipoMeta,
                    'meta_padrao' => $meta,
                    'unidade_padrao' => $unidade,
                    'dificuldade_padrao' => $dificuldade,
                    'prioridade_mvp' => $prioridade,
                ],
            );
        }
    }
}
