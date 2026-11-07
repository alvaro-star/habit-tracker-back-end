<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dragão ativo do usuário: um usuário pode ter vários dragões (os do
 * Santuário), mas só um com status ATIVO, apontado por users.fk_id_dragao.
 * Os usuários que já têm um dragão ativo recebem o mais recente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('fk_id_dragao')->nullable()->constrained('dragao')->nullOnDelete();
        });

        DB::table('dragao')
            ->where('status', 'ATIVO')
            ->orderBy('id')
            ->get(['id', 'fk_id_usuario'])
            ->each(fn ($dragao) => DB::table('users')->where('id', $dragao->fk_id_usuario)->update(['fk_id_dragao' => $dragao->id]));
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fk_id_dragao');
        });
    }
};
