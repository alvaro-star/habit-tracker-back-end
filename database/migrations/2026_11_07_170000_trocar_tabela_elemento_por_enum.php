<?php

use App\Enums\ElementoDragao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Troca a tabela elemento pelo enum ElementoDragao (FOGO, TERRA, AGUA, AR).
 * O dragão ganha a coluna elemento (o elemento atual, obrigatório a partir dos
 * 6.000 XP do ciclo), e a escolha_elemento passa a guardar o elemento como texto.
 * O cenário e a arte de cada elemento ficam a cargo do front-end.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dragao', function (Blueprint $table) {
            $table->string('elemento', 10)->nullable();
        });

        Schema::table('escolha_elemento', function (Blueprint $table) {
            $table->string('elemento', 10)->nullable();
        });

        // Converte as escolhas existentes pelo nome do antigo catálogo (ex.: "Água" → AGUA).
        DB::table('escolha_elemento')
            ->join('elemento', 'elemento.id', '=', 'escolha_elemento.fk_id_elemento')
            ->get(['escolha_elemento.id', 'elemento.nome'])
            ->each(function ($escolha) {
                $elemento = ElementoDragao::tryFrom(Str::upper(Str::ascii($escolha->nome)));

                if ($elemento === null) {
                    throw new RuntimeException("Elemento \"{$escolha->nome}\" sem correspondente no enum ElementoDragao; converta-o antes de migrar.");
                }

                DB::table('escolha_elemento')->where('id', $escolha->id)->update(['elemento' => $elemento->value]);
            });

        Schema::table('escolha_elemento', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fk_id_elemento');
            $table->string('elemento', 10)->nullable(false)->change();
        });

        Schema::dropIfExists('elemento');
    }

    public function down(): void
    {
        Schema::create('elemento', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 20)->unique();
            $table->string('cenario_url', 255);
        });

        Schema::table('escolha_elemento', function (Blueprint $table) {
            $table->foreignId('fk_id_elemento')->nullable()->constrained('elemento');
            $table->dropColumn('elemento');
        });

        Schema::table('dragao', function (Blueprint $table) {
            $table->dropColumn('elemento');
        });
    }
};
