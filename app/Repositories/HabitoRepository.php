<?php

namespace App\Repositories;

use App\Enums\DiaSemana;
use App\Enums\Frequencia;
use App\Enums\Polaridade;
use App\Enums\StatusRegistro;
use App\Models\Habito;
use Carbon\CarbonInterface;
use Closure;
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

    /**
     * Hábitos ruins ativos do usuário, com a ultima_recaida já carregada: a base
     * do contador de abstinência das estatísticas (MOD7).
     *
     * @return Collection<int, Habito>
     */
    public function ruinsAtivosDoUsuario(int $usuarioId): Collection
    {
        return $this->consultaDoUsuario($usuarioId)
            ->where('polaridade', Polaridade::RUIM->value)
            ->where('ativo', true)
            ->orderBy('id')
            ->get();
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
            ->where(fn (Builder $consulta) => $this->caiNoDia($consulta, $data, $diaSemana))
            ->orderBy('id')
            ->get();
    }

    /**
     * Hábitos bons e ativos, de qualquer usuário, com lembrete entre os horários,
     * que caem na data, ainda sem lembrete nela e sem check-in concluído.
     *
     * @param  Closure(Habito): void  $callback
     */
    public function comLembretePendente(CarbonInterface $data, string $inicio, string $fim, Closure $callback): void
    {
        $dia = $data->toDateString();

        $this->consulta()
            ->with('usuario')
            ->where('ativo', true)
            ->where('polaridade', Polaridade::BOM->value)
            ->whereBetween('horario_lembrete', [$inicio, $fim])
            ->whereDate('created_at', '<=', $dia)
            ->where(fn (Builder $consulta) => $consulta->whereNull('ultimo_lembrete_em')->orWhere('ultimo_lembrete_em', '<>', $dia))
            ->whereDoesntHave('registros', fn (Builder $registros) => $registros
                ->whereDate('data', $dia)
                ->where('status', StatusRegistro::CONCLUIDO->value))
            ->where(fn (Builder $consulta) => $this->caiNoDia($consulta, $data, DiaSemana::daData($data)))
            ->eachById($callback);
    }

    /** Marca o lembrete do dia; devolve false se outra execução já o marcou. */
    public function marcarLembreteEnviado(int $habitoId, CarbonInterface $data): bool
    {
        $dia = $data->toDateString();

        return $this->consulta()->whereKey($habitoId)
            ->where(fn (Builder $consulta) => $consulta->whereNull('ultimo_lembrete_em')->orWhere('ultimo_lembrete_em', '<>', $dia))
            ->toBase()->update(['ultimo_lembrete_em' => $dia]) === 1;
    }

    /**
     * Os diários, os semanais do dia da semana e os mensais do dia do mês.
     *
     * @param  Builder<Habito>  $consulta
     * @return Builder<Habito>
     */
    private function caiNoDia(Builder $consulta, CarbonInterface $data, DiaSemana $diaSemana): Builder
    {
        return $consulta
            ->where('frequencia', Frequencia::DIARIA->value)
            ->orWhere(fn (Builder $semanal) => $semanal
                ->where('frequencia', Frequencia::SEMANAL->value)
                ->where('dia_frequencia', $diaSemana->value))
            ->orWhere(fn (Builder $mensal) => $mensal
                ->where('frequencia', Frequencia::MENSAL->value)
                ->where('dia_frequencia', (string) $data->day));
    }
}
