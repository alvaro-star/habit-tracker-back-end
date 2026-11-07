<?php

namespace App\Services;

use App\DTOs\CheckIns\RegistroHabitoDTO;
use App\Enums\StatusRegistro;
use App\Enums\TipoMeta;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Repositories\HabitoRepository;
use App\Repositories\RegistroHabitoRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** @property RegistroHabitoRepository $repositorio */
class RegistroHabitoService extends BaseService
{
    public function __construct(
        RegistroHabitoRepository $repositorio,
        protected HabitoRepository $habitos,
        protected XpDiarioService $xpDiario,
    ) {
        parent::__construct($repositorio);
    }

    /**
     * Marca ou desmarca o hábito no dia; é a única forma de gravar um check-in.
     *
     * Antes de gravar, confere a data do DTO: ela não pode ser anterior ao
     * nascimento do dragão ativo do usuário (422) nem futura (422), e não pode
     * ser anterior à criação do hábito (409). Com checked = true, o check-in do
     * hábito no dia é criado ou atualizado, com o status e o percentual
     * definidos pelo tipo de meta (comCumprimento); com checked = false, é excluído.
     * Nos dois casos, o XP daquele dia (passado ou hoje) é recalculado e gravado,
     * e a diferença vai para o dragão e para o usuário.
     *
     * @return RegistroHabito|null o check-in gravado, ou null ao desmarcar
     *
     * @throws RegraDeNegocioException
     */
    public function registrar(User $usuario, RegistroHabitoDTO $dto): ?RegistroHabito
    {
        $data = $dto->data->startOfDay();
        $hoje = CarbonImmutable::today();

        /** @var Habito $habito */
        $habito = $this->habitos->buscarDoUsuarioOuFalhar($usuario->id, $dto->habitoId);
        $this->validarData($usuario, $habito, $data, $hoje);

        return DB::transaction(function () use ($usuario, $habito, $dto, $data) {
            $existente = $this->repositorio->doHabitoNoDia($dto->habitoId, $data->toDateString());
            $registro = null;

            if ($dto->checked) {
                $dados = $this->comCumprimento($habito, $dto->toArray());
                /** @var RegistroHabito $registro */
                $registro = $existente === null
                    ? $this->repositorio->criar($dados)
                    : $this->repositorio->atualizar($existente, $dados);
            } elseif ($existente !== null) {
                $this->repositorio->excluir($existente);
            }

            // O XP do dia fica sempre gravado em xp_diario, inclusive o de hoje.
            $this->xpDiario->recalcularDia($usuario, $data);

            return $registro;
        });
    }

    /**
     * Define o status e o percentual do check-in pelo tipo de meta do hábito.
     *
     * - REALIZACAO: vale o status enviado (sem status, NAO_CONCLUIDO). CONCLUIDO
     *   grava 100% e NAO_CONCLUIDO, 0%; EM_ANDAMENTO usa o percentual enviado.
     * - TEMPO e QUANTIDADE: o status enviado é ignorado; o valor_realizado
     *   (minutos ou quantidade) é comparado com o valor_meta do hábito. Nulo ou
     *   zero → NAO_CONCLUIDO (0%); igual ou acima da meta → CONCLUIDO (100%);
     *   abaixo da meta → EM_ANDAMENTO, com o percentual proporcional. Sem
     *   valor_meta no hábito, qualquer valor acima de zero conclui o hábito.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function comCumprimento(Habito $habito, array $dados): array
    {
        if (TipoMeta::from($habito->tipo_meta) === TipoMeta::REALIZACAO) {
            $status = StatusRegistro::from($dados['status'] ?? StatusRegistro::NAO_CONCLUIDO->value);

            return [...$dados, 'status' => $status->value, ...match ($status) {
                StatusRegistro::CONCLUIDO => ['percentual' => 100],
                StatusRegistro::NAO_CONCLUIDO => ['percentual' => 0],
                StatusRegistro::EM_ANDAMENTO => [],
            }];
        }

        $valor = isset($dados['valor_realizado']) ? (float) $dados['valor_realizado'] : null;
        $meta = $habito->valor_meta !== null ? (float) $habito->valor_meta : null;

        [$status, $percentual] = match (true) {
            $valor === null || $valor <= 0 => [StatusRegistro::NAO_CONCLUIDO, 0],
            $meta === null || $meta <= 0 || $valor >= $meta => [StatusRegistro::CONCLUIDO, 100],
            default => [StatusRegistro::EM_ANDAMENTO, round($valor / $meta * 100, 2)],
        };

        return [...$dados, 'valor_realizado' => $valor, 'status' => $status->value, 'percentual' => $percentual];
    }

    /** @throws RegraDeNegocioException */
    private function validarData(User $usuario, Habito $habito, CarbonImmutable $data, CarbonImmutable $hoje): void
    {
        $dragao = $usuario->dragaoAtivo;

        if ($dragao === null) {
            throw new RegraDeNegocioException('fk_id_habito', 'O usuário não tem um dragão ativo: crie um dragão antes de registrar check-ins.');
        }

        $nascimentoDoDragao = CarbonImmutable::parse($dragao->data_nascimento->toDateString());
        $criacaoDoHabito = CarbonImmutable::parse($habito->created_at->toDateString());

        if ($data->lessThan($nascimentoDoDragao)) {
            throw new RegraDeNegocioException('data', 'A data do check-in é anterior ao nascimento do dragão ativo ('.$dragao->data_nascimento->toDateString().').');
        }

        if ($data->greaterThan($hoje)) {
            throw new RegraDeNegocioException('data', 'A data do check-in não pode ser futura.');
        }

        if ($data->lessThan($criacaoDoHabito)) {
            throw new RegraDeNegocioException(
                'data',
                'A data do check-in é anterior à criação do hábito ('.$habito->created_at->toDateString().').',
                Response::HTTP_CONFLICT,
            );
        }
    }
}
