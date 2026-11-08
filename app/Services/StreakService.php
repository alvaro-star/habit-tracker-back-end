<?php

namespace App\Services;

use App\Repositories\RegistroHabitoRepository;
use App\Repositories\UsuarioRepository;
use Carbon\CarbonImmutable;

/**
 * Sequência (streak): dias seguidos com ao menos um check-in CONCLUIDO. Hoje
 * ainda não cumprido não quebra a sequência, que continua valendo até ontem.
 */
class StreakService
{
    public function __construct(
        protected RegistroHabitoRepository $registros,
        protected UsuarioRepository $usuarios,
    ) {}

    /** Recalcula pelo histórico, grava em users.streak_atual e devolve o valor. */
    public function atualizar(int $usuarioId): int
    {
        $streak = $this->calcular($usuarioId);
        $this->usuarios->definirStreak($usuarioId, $streak);

        return $streak;
    }

    public function calcular(int $usuarioId): int
    {
        $dias = array_flip($this->registros->diasConcluidosDoUsuario($usuarioId));
        $dia = CarbonImmutable::today();

        if (! isset($dias[$dia->toDateString()])) {
            $dia = $dia->subDay();
        }

        $streak = 0;

        while (isset($dias[$dia->toDateString()])) {
            $streak++;
            $dia = $dia->subDay();
        }

        return $streak;
    }

    public function concluiuHoje(int $usuarioId): bool
    {
        return in_array(CarbonImmutable::today()->toDateString(), $this->registros->diasConcluidosDoUsuario($usuarioId), true);
    }
}
