<?php

namespace App\Repositories;

use App\Models\User;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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

    /** Um aparelho pertence a uma conta por vez: tira o token de quem o usava antes. */
    public function definirTokenDispositivo(int $usuarioId, string $token): void
    {
        DB::transaction(function () use ($usuarioId, $token) {
            $this->consulta()->where('token_dispositivo', $token)->whereKeyNot($usuarioId)
                ->toBase()->update(['token_dispositivo' => null]);
            $this->consulta()->whereKey($usuarioId)->toBase()->update(['token_dispositivo' => $token]);
        });
    }

    public function limparTokenDispositivo(int $usuarioId): void
    {
        $this->consulta()->whereKey($usuarioId)->toBase()->update(['token_dispositivo' => null]);
    }

    public function removerTokenDispositivo(string $token): void
    {
        $this->consulta()->where('token_dispositivo', $token)->toBase()->update(['token_dispositivo' => null]);
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
     * Usuários cujo último acesso está no intervalo. Quem nunca acessou conta a
     * partir do cadastro.
     *
     * @param  Closure(User): void  $callback
     */
    public function comUltimoAcessoEntre(CarbonInterface $inicio, CarbonInterface $fim, Closure $callback): void
    {
        $this->consulta()
            ->where(fn ($consulta) => $consulta->whereBetween('ultimo_acesso', [$inicio, $fim])
                ->orWhere(fn ($semAcesso) => $semAcesso->whereNull('ultimo_acesso')->whereBetween('created_at', [$inicio, $fim])))
            ->eachById($callback);
    }

    /**
     * Usuários cadastrados antes do início informado e que não usaram a API nem
     * fizeram login desde então.
     *
     * @param  Closure(User): void  $callback
     */
    public function comTodosSemAcessoDesde(CarbonInterface $inicio, Closure $callback): void
    {
        $this->consultaSemAcessoDesde($inicio)->eachById($callback);
    }

    public function encontrarSemAcessoDesde(int $usuarioId, CarbonInterface $inicio): ?User
    {
        return $this->consultaSemAcessoDesde($inicio)->find($usuarioId);
    }

    /** @return Builder<User> */
    private function consultaSemAcessoDesde(CarbonInterface $inicio): Builder
    {
        return $this->consulta()
            ->where('created_at', '<', $inicio)
            ->where(fn ($consulta) => $consulta->whereNull('ultimo_acesso')->orWhere('ultimo_acesso', '<', $inicio))
            ->where(fn ($consulta) => $consulta->whereNull('ultimo_acesso_at')->orWhere('ultimo_acesso_at', '<', $inicio));
    }

    /** @param Closure(User): void $callback */
    public function comTodos(Closure $callback): void
    {
        $this->consulta()->select('id')->eachById($callback);
    }

    public function encontrar(int $usuarioId): ?User
    {
        return $this->consulta()->find($usuarioId);
    }
}
