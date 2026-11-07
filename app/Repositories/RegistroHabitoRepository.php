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

    /**
     * Registros dos hábitos informados na data, do mais antigo para o mais recente.
     *
     * @param  array<int, int>  $habitoIds
     * @return Collection<int, RegistroHabito>
     */
    public function dosHabitosNoDia(array $habitoIds, CarbonInterface $data): Collection
    {
        return $this->consulta()
            ->whereIn('fk_id_habito', $habitoIds)
            ->whereDate('data', $data->toDateString())
            ->orderBy('id')
            ->get();
    }
}
