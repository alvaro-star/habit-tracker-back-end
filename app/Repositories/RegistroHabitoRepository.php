<?php

namespace App\Repositories;

use App\Models\RegistroHabito;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RegistroHabitoRepository extends BaseRepository
{
    protected string $model = RegistroHabito::class;

    /** O dono do registro é o dono do hábito a que ele pertence. */
    protected function consultaDoUsuario(int $usuarioId): Builder
    {
        return $this->consulta()->whereHas('habito', fn ($habito) => $habito->where('fk_id_usuario', $usuarioId));
    }

    /** Check-in do hábito no dia, buscado pelo índice (fk_id_habito, data). */
    public function doHabitoNoDia(int $habitoId, string $data): ?RegistroHabito
    {
        return $this->consulta()
            ->where('fk_id_habito', $habitoId)
            ->where('data', $data)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Registros dos hábitos informados na data, do mais antigo para o mais recente.
     * Só valem check-ins de hábitos que já existiam na data (criados até ela).
     *
     * @param  array<int, int>  $habitoIds
     * @return Collection<int, RegistroHabito>
     */
    public function dosHabitosNoDia(array $habitoIds, CarbonInterface $data): Collection
    {
        return $this->consulta()
            ->whereIn('fk_id_habito', $habitoIds)
            ->whereDate('data', $data->toDateString())
            ->whereHas('habito', fn ($habito) => $habito->whereDate('created_at', '<=', $data->toDateString()))
            ->orderBy('id')
            ->get();
    }
}
