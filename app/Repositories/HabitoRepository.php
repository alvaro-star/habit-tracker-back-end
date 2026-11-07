<?php

namespace App\Repositories;

use App\Enums\DiaSemana;
use App\Enums\Frequencia;
use App\Models\Habito;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class HabitoRepository extends BaseRepository
{
    protected string $model = Habito::class;

    /**
     * Hábitos ativos do usuário, já criados na data, que caem naquele dia:
     * os diários, os semanais do dia da semana e os mensais do dia do mês.
     *
     * @return Collection<int, Habito>
     */
    public function ativosDoUsuarioNoDia(int $usuarioId, CarbonInterface $data, DiaSemana $diaSemana): Collection
    {
        return $this->consultaDoUsuario($usuarioId)
            ->where('ativo', true)
            ->whereDate('created_at', '<=', $data->toDateString())
            ->where(fn (Builder $consulta) => $consulta
                ->where('frequencia', Frequencia::DIARIA->value)
                ->orWhere(fn (Builder $semanal) => $semanal
                    ->where('frequencia', Frequencia::SEMANAL->value)
                    ->where('dia_frequencia', $diaSemana->value))
                ->orWhere(fn (Builder $mensal) => $mensal
                    ->where('frequencia', Frequencia::MENSAL->value)
                    ->where('dia_frequencia', (string) $data->day)))
            ->orderBy('id')
            ->get();
    }
}
