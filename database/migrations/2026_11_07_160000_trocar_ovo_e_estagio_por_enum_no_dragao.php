<?php

use App\Enums\EstagioDragao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove as tabelas ovo e estagio. O estágio de crescimento do dragão passa a
 * ser o enum EstagioDragao, guardado em dragao.estagio (calculado pelo
 * xp_ciclo) e em escolha_elemento.estagio (o estágio do dragão na escolha).
 * O ovo deixa de ser registrado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dragao', function (Blueprint $table) {
            $table->string('estagio', 20)->default(EstagioDragao::OVO->value);
        });

        DB::table('dragao')->get(['id', 'xp_ciclo'])->each(
            fn ($dragao) => DB::table('dragao')->where('id', $dragao->id)->update([
                'estagio' => EstagioDragao::doXp((int) $dragao->xp_ciclo)->value,
            ]),
        );

        // A escolha recebe o estágio pela ordem do antigo catálogo (1 = Ovo … 5 = Adulto final).
        Schema::table('escolha_elemento', function (Blueprint $table) {
            $table->string('estagio', 20)->default(EstagioDragao::OVO->value);
        });

        $porOrdem = array_column(EstagioDragao::cases(), 'value');
        DB::table('escolha_elemento')
            ->join('estagio', 'estagio.id', '=', 'escolha_elemento.fk_id_estagio')
            ->get(['escolha_elemento.id', 'estagio.ordem'])
            ->each(fn ($escolha) => DB::table('escolha_elemento')->where('id', $escolha->id)->update([
                'estagio' => $porOrdem[$escolha->ordem - 1] ?? EstagioDragao::OVO->value,
            ]));

        Schema::table('escolha_elemento', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fk_id_estagio');
        });

        Schema::table('dragao', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fk_id_estagio');
            $table->dropConstrainedForeignId('fk_id_ovo');
        });

        Schema::dropIfExists('estagio');
        Schema::dropIfExists('ovo');
    }

    public function down(): void
    {
        Schema::create('estagio', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 30);
            $table->integer('ordem');
            $table->integer('xp_marco');
        });

        Schema::create('ovo', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 40);
            $table->string('imagem_url', 255);
        });

        Schema::table('dragao', function (Blueprint $table) {
            $table->foreignId('fk_id_ovo')->nullable()->constrained('ovo');
            $table->foreignId('fk_id_estagio')->nullable()->constrained('estagio');
            $table->dropColumn('estagio');
        });

        Schema::table('escolha_elemento', function (Blueprint $table) {
            $table->foreignId('fk_id_estagio')->nullable()->constrained('estagio');
            $table->dropColumn('estagio');
        });
    }
};
