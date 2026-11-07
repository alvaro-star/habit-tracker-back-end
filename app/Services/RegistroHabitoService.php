<?php

namespace App\Services;

use App\DTOs\CheckIns\RegistroHabitoDTO;
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
     * hábito no dia é criado ou atualizado; com checked = false, é excluído.
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

        return DB::transaction(function () use ($usuario, $dto, $data) {
            $existente = $this->repositorio->doHabitoNoDia($dto->habitoId, $data->toDateString());
            $registro = null;

            if ($dto->checked) {
                $dados = $dto->toArray();
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
