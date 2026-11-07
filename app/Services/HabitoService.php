<?php

namespace App\Services;

use App\DTOs\DTO;
use App\Enums\DiaSemana;
use App\Enums\Dificuldade;
use App\Enums\Frequencia;
use App\Enums\StatusRegistro;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Repositories\HabitoRepository;
use App\Repositories\RegistroHabitoRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** @property HabitoRepository $repositorio */
class HabitoService extends BaseService
{
    public function __construct(
        HabitoRepository $repositorio,
        protected RegistroHabitoRepository $registros,
    ) {
        parent::__construct($repositorio);
    }

    /** @param array<string, mixed>|DTO $dados */
    public function criar(array|DTO $dados): Model
    {
        return $this->repositorio->criar($this->comFrequenciaValidada($this->paraArray($dados)));
    }

    /** @param array<string, mixed>|DTO $dados */
    public function criarParaUsuario(int $usuarioId, array|DTO $dados): Model
    {
        return $this->repositorio->criar([
            ...$this->comFrequenciaValidada($this->paraArray($dados)),
            'fk_id_usuario' => $usuarioId,
        ]);
    }

    /** @param array<string, mixed>|DTO $dados */
    public function atualizar(int|string $id, array|DTO $dados): Model
    {
        /** @var Habito $habito */
        $habito = $this->buscar($id);

        return $this->repositorio->atualizar($habito, $this->comFrequenciaValidada($this->paraArray($dados), $habito));
    }

    /** @param array<string, mixed>|DTO $dados */
    public function atualizarDoUsuario(int $usuarioId, int|string $id, array|DTO $dados): Model
    {
        /** @var Habito $habito */
        $habito = $this->buscarDoUsuario($usuarioId, $id);

        return $this->repositorio->atualizar($habito, $this->comFrequenciaValidada($this->paraArray($dados), $habito));
    }

    /**
     * Confere se o dia_frequencia combina com a frequência do hábito:
     * diário sem dia, semanal com SEG a DOM e mensal com um dia de 1 a 31.
     *
     * @throws RegraDeNegocioException respondida como 422
     */
    public function validarFrequencia(Frequencia $frequencia, int|string|DiaSemana|null $dia): void
    {
        if (! $frequencia->aceitaDia($dia)) {
            throw new RegraDeNegocioException('dia_frequencia', $frequencia->mensagemDiaInvalido());
        }
    }

    /**
     * Hábitos ativos do usuário que devem ser cumpridos na data: todos os
     * diários, os semanais daquele dia da semana e os mensais daquele dia do
     * mês. Um hábito mensal num dia que o mês não tem (ex.: 31 em abril) não
     * aparece naquele mês. Hábitos criados depois da data ficam de fora.
     *
     * @return Collection<int, Habito>
     */
    public function habitosDiarios(int $usuarioId, CarbonInterface $data): Collection
    {
        return $this->repositorio->ativosDoUsuarioNoDia($usuarioId, $data, DiaSemana::daData($data));
    }

    /**
     * XP do dia (ESCOPO.md, seção 3.3): XP = (∑W cumpridos / ∑W total) × teto diário.
     *
     * O ∑W total é a soma dos pesos dos hábitos do dia (habitosDiarios), sem os
     * semanais, que o escopo apura num pool próprio ao fim da semana. O ∑W
     * cumpridos vem de pesoRealizadoDoDia(). O XP é arredondado para inteiro,
     * como a coluna xp_diario.xp_ganho.
     *
     * @return array{
     *     data: string,
     *     habitos: array<int, array{id: int, nome: string, frequencia: string, peso: int, status: ?string, percentual: ?float, peso_realizado: float}>,
     *     peso_total: int,
     *     peso_realizado: float,
     *     percentual_ponderado: float,
     *     teto_diario: int,
     *     xp: int,
     * }
     */
    public function xpDoDia(int $usuarioId, CarbonInterface $data): array
    {
        $habitos = $this->habitosDiarios($usuarioId, $data)
            ->reject(fn (Habito $habito) => $habito->frequencia === Frequencia::SEMANAL->value)
            ->values();

        $pesoTotal = (int) $habitos->sum('peso');
        $realizado = $this->pesoRealizadoDoDia($habitos, $data);
        $pesoRealizado = round(array_sum(array_column($realizado, 'peso_realizado')), 2);
        $proporcao = $pesoTotal > 0 ? min($pesoRealizado / $pesoTotal, 1) : 0;
        $teto = (int) config('progression.teto_diario');

        return [
            'data' => $data->toDateString(),
            'habitos' => $habitos->map(fn (Habito $habito) => [
                'id' => $habito->id,
                'nome' => $habito->nome,
                'frequencia' => $habito->frequencia,
                'peso' => $habito->peso,
                ...$realizado[$habito->id],
            ])->all(),
            'peso_total' => $pesoTotal,
            'peso_realizado' => $pesoRealizado,
            'percentual_ponderado' => round($proporcao * 100, 2),
            'teto_diario' => $teto,
            'xp' => (int) round($proporcao * $teto),
        ];
    }

    /**
     * Peso cumprido de cada hábito na data, a partir do check-in (registro_habito):
     * CONCLUIDO soma o peso inteiro, EM_ANDAMENTO soma o peso proporcional ao
     * percentual, e NAO_CONCLUIDO ou sem check-in soma zero. Se houver mais de
     * um check-in do hábito no dia, vale o mais recente.
     *
     * @param  Collection<int, Habito>  $habitos
     * @return array<int, array{status: ?string, percentual: ?float, peso_realizado: float}> indexado pelo id do hábito
     */
    public function pesoRealizadoDoDia(Collection $habitos, CarbonInterface $data): array
    {
        $registros = $this->registros->dosHabitosNoDia($habitos->modelKeys(), $data)->keyBy('fk_id_habito');

        return $habitos->mapWithKeys(function (Habito $habito) use ($registros) {
            /** @var RegistroHabito|null $registro */
            $registro = $registros->get($habito->id);
            $percentual = $registro === null ? null : (float) $registro->percentual;

            $pesoRealizado = match ($registro?->status) {
                StatusRegistro::CONCLUIDO->value => (float) $habito->peso,
                StatusRegistro::EM_ANDAMENTO->value => round($habito->peso * $percentual / 100, 2),
                default => 0.0,
            };

            return [$habito->id => [
                'status' => $registro?->status,
                'percentual' => $percentual,
                'peso_realizado' => $pesoRealizado,
            ]];
        })->all();
    }

    /**
     * Completa os dados com a frequência e o dia já salvos (num PATCH parcial)
     * e valida a combinação. Ao virar diário, o dia antigo é apagado.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function comFrequenciaValidada(array $dados, ?Habito $atual = null): array
    {
        $frequencia = Frequencia::from($dados['frequencia'] ?? $atual?->frequencia ?? Frequencia::DIARIA->value);

        if ($frequencia === Frequencia::DIARIA && ! array_key_exists('dia_frequencia', $dados)) {
            $dados['dia_frequencia'] = null;
        }

        $dia = array_key_exists('dia_frequencia', $dados) ? $dados['dia_frequencia'] : $atual?->dia_frequencia;
        $this->validarFrequencia($frequencia, $dia);

        if ($dia !== null) {
            $dados['dia_frequencia'] = $dia instanceof DiaSemana ? $dia->value : (string) $dia;
        }

        return $dados;
    }

    /**
     * O peso é sempre gerado pela dificuldade (ESCOPO.md, seção 3.2): um peso
     * informado é descartado, e trocar a dificuldade recalcula o peso. Vale
     * para todas as operações de criação e edição do BaseService.
     */
    protected function paraArray(array|DTO $dados): array
    {
        $dados = parent::paraArray($dados);
        unset($dados['peso']);

        if (isset($dados['dificuldade'])) {
            $dificuldade = $dados['dificuldade'] instanceof Dificuldade
                ? $dados['dificuldade']
                : Dificuldade::from($dados['dificuldade']);

            $dados['peso'] = $dificuldade->peso();
        }

        return $dados;
    }
}
