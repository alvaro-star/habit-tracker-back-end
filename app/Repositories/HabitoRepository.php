<?php

namespace App\Repositories;

use App\Enums\DiaSemana;
use App\Enums\Frequencia;
use App\Enums\StatusRegistro;
use App\Models\Habito;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** @extends BaseRepository<Habito> */
class HabitoRepository extends BaseRepository
{
    protected string $model = Habito::class;

    /**
     * Os hábitos do usuário já vêm com a data da última recaída (o check-in
     * mais recente que não seja NAO_CONCLUIDO), usada pelo contador de
     * abstinência dos hábitos ruins sem uma query por hábito.
     *
     * @return Builder<Habito>
     */
    protected function consultaDoUsuario(int $usuarioId): Builder
    {
        return parent::consultaDoUsuario($usuarioId)->withMax(
            ['registros as ultima_recaida' => fn (Builder $registros) => $registros->where('status', '!=', StatusRegistro::NAO_CONCLUIDO->value)],
            'data',
        );
    }

    /** Diz se o hábito já tem algum check-in (registro_habito). */
    public function temRegistros(Habito $habito): bool
    {
        return $habito->registros()->exists();
    }

    /**
     * Hábitos ativos do usuário, já criados na data, que caem naquele dia:
     * os diários, os semanais do dia da semana e os mensais do dia do mês.
     * É a base do XP diário (XpDiarioService::xpDoDia) e dos hábitos do dia.
     *
     * TODO: usar o inactivated_at para desconsiderar o hábito só a partir do dia
     * em que foi desativado. Hoje o filtro ativo = true tira o hábito de todos os
     * dias, inclusive dos anteriores à desativação, o que muda o XP diário e o
     * progresso dos hábitos de períodos em que ele ainda estava ativo.
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
