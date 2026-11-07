<?php

namespace App\Services;

use App\Enums\DiaSemana;
use App\Enums\StatusRegistro;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Models\XpDiario;
use App\Repositories\DragaoRepository;
use App\Repositories\HabitoRepository;
use App\Repositories\RegistroHabitoRepository;
use App\Repositories\UsuarioRepository;
use App\Repositories\XpDiarioRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/** @property XpDiarioRepository $repositorio */
class XpDiarioService extends BaseService
{
    public function __construct(
        XpDiarioRepository $repositorio,
        // O repositório, e não o HabitoService: o HabitoService usa este service (ao criar ou desativar hábitos).
        protected HabitoRepository $habitos,
        protected RegistroHabitoRepository $registros,
        protected DragaoRepository $dragoes,
        protected UsuarioRepository $usuarios,
    ) {
        parent::__construct($repositorio);
    }

    /**
     * XP diário do usuário registrado no intervalo, do dia mais recente para o
     * mais antigo. Tudo vem do banco, inclusive o dia de hoje: o registro do dia
     * é atualizado sempre que um check-in é marcado ou desmarcado, ou um hábito
     * é criado ou desativado (atualizarXpDeHoje / recalcularDia).
     *
     * @return Collection<int, XpDiario>
     */
    public function historicoDoUsuario(User $usuario, CarbonInterface $inicio, CarbonInterface $fim): Collection
    {
        return $this->repositorio->doUsuarioNoIntervalo($usuario->id, $inicio, $fim);
    }

    /**
     * Recalcula e grava o XP de hoje do usuário, acertando o XP do dragão e o
     * vitalício pela diferença. É a função a chamar sempre que algo muda o XP
     * de hoje: check-in de hoje, hábito criado ou hábito desativado.
     *
     * @return int a diferença de XP aplicada (negativa quando o XP de hoje cai)
     */
    public function atualizarXpDeHoje(int $usuarioId): int
    {
        /** @var User $usuario */
        $usuario = $this->usuarios->buscarOuFalhar($usuarioId);

        return $this->recalcularDia($usuario, CarbonImmutable::today());
    }

    /**
     * Recalcula o XP de um dia (passado ou hoje) e acerta os saldos (MOD5-TASK8).
     *
     * O XP já creditado do dia é o xp_ganho do registro em xp_diario (0 se o dia
     * ainda não tem registro). O registro é criado ou atualizado com o XP novo,
     * no dragão ativo, e a diferença (novo − creditado) é somada ao xp_ciclo do
     * dragão e ao xp_vitalicio do usuário. Assim, marcar e desmarcar o mesmo
     * hábito volta os saldos ao que eram. Sem dragão ativo não há onde creditar
     * o XP (xp_diario.fk_id_dragao é obrigatório), então nada é gravado.
     *
     * @return int a diferença de XP aplicada
     */
    public function recalcularDia(User $usuario, CarbonInterface $data): int
    {
        if ($usuario->fk_id_dragao === null) {
            return 0;
        }

        $xp = $this->xpDoDia($usuario->id, $data);
        $registro = $this->repositorio->doUsuarioNoDia($usuario->id, $data);
        $diferenca = $xp['xp'] - ($registro?->xp_ganho ?? 0);

        $valores = [
            'fk_id_dragao' => $usuario->fk_id_dragao,
            'percentual_ponderado' => $xp['percentual_ponderado'],
            'xp_ganho' => $xp['xp'],
        ];

        if ($registro === null) {
            $this->repositorio->criar([...$valores, 'fk_id_usuario' => $usuario->id, 'data' => $data->toDateString()]);
        } else {
            $this->repositorio->atualizar($registro, $valores);
        }

        if ($diferenca !== 0) {
            $this->dragoes->somarXpCiclo($usuario->fk_id_dragao, $diferenca);
            $this->usuarios->somarXpVitalicio($usuario->id, $diferenca);
        }

        return $diferenca;
    }

    /**
     * XP do dia (ESCOPO.md, seção 3.3): XP = (∑W cumpridos / ∑W total) × teto diário.
     *
     * Calcula de forma dinâmica para qualquer dia. O ∑W total é a soma dos pesos
     * dos hábitos ativos que já existiam na data (criados até ela) e que caem
     * naquele dia, seja qual for a frequência: todos os diários, os semanais do
     * dia da semana da data e os mensais do dia do mês. Não há pool semanal: o
     * que conta é o cumprimento do dia. O ∑W cumpridos vem de pesoRealizadoDoDia(). O XP é arredondado para inteiro,
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
        // TODO: desconsiderar os hábitos desativados no período só a partir do inactivated_at
        // (ver HabitoRepository::ativosDoUsuarioNoDia), em vez de tirá-los de todos os dias.
        $habitos = $this->habitos->ativosDoUsuarioNoDia($usuarioId, $data, DiaSemana::daData($data));

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
}
