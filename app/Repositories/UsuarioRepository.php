<?php

namespace App\Repositories;

use App\Models\User;
use Carbon\CarbonInterface;
use Closure;

/** @extends BaseRepository<User> */
class UsuarioRepository extends BaseRepository
{
    protected string $model = User::class;

    /**
     * Soma (ou subtrai, se negativa) a diferença ao xp_vitalicio, sem deixá-lo
     * abaixo de zero. Usa forceFill porque o xp_vitalicio fica fora do Fillable.
     */
    public function somarXpVitalicio(int $usuarioId, int $diferenca): void
    {
        /** @var User $usuario */
        $usuario = $this->buscarOuFalhar($usuarioId);
        $usuario->forceFill(['xp_vitalicio' => max(0, $usuario->xp_vitalicio + $diferenca)])->save();
    }

    /**
     * Grava o nome e os objetivos (categorias) escolhidos no onboarding e marca
     * o onboarding como concluído. Usa forceFill porque o onboarding_concluido
     * fica fora do Fillable.
     *
     * @param  array<int, int>  $categoriaIds
     */
    public function concluirOnboarding(User $usuario, string $nome, array $categoriaIds): void
    {
        $usuario->forceFill(['name' => $nome, 'onboarding_concluido' => true])->save();
        $usuario->objetivos()->sync($categoriaIds);
    }

    /** Não usa o Eloquent para não mexer no updated_at, já que o perfil não mudou. */
    public function registrarAcesso(int $usuarioId): void
    {
        $this->consulta()->whereKey($usuarioId)->toBase()->update(['ultimo_acesso' => now()]);
    }

    public function definirStreak(int $usuarioId, int $streak): void
    {
        $this->consulta()->whereKey($usuarioId)->toBase()->update(['streak_atual' => $streak]);
    }

    /** @param Closure(User): void $callback */
    public function comStreakAtiva(Closure $callback): void
    {
        $this->consulta()->where('streak_atual', '>', 0)->eachById($callback);
    }

    /**
     * Usuários sem acesso desde a data. Quem nunca acessou conta a partir do cadastro.
     *
     * @param  Closure(User): void  $callback
     */
    public function inativosDesde(CarbonInterface $limite, Closure $callback): void
    {
        $this->consulta()
            ->where(fn ($consulta) => $consulta->where('ultimo_acesso', '<=', $limite)
                ->orWhere(fn ($semAcesso) => $semAcesso->whereNull('ultimo_acesso')->where('created_at', '<=', $limite)))
            ->eachById($callback);
    }
}
