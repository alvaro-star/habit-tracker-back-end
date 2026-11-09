<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('ultimo_acesso_at')->nullable()->after('email_verified_at');
            $table->boolean('onboarding_concluido')->default(false)->after('ultimo_acesso_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['ultimo_acesso_at', 'onboarding_concluido']);
        });
    }
};
