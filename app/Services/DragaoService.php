<?php

namespace App\Services;

use App\DTOs\DTO;
use App\Enums\ElementoDragao;
use App\Enums\EstagioDragao;
use App\Enums\StatusDragao;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Dragao;
use App\Repositories\DragaoRepository;
use App\Repositories\EscolhaElementoRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Um usuário pode ter vários dragões (os do Santuário), mas só um ATIVO.
 * O dragão ativo fica em users.fk_id_dragao, mantido por este service.
 *
 * @property DragaoRepository $repositorio
 *
 * @extends BaseService<Dragao>
 */
class DragaoService extends BaseService
{
    public function __construct(
        DragaoRepository $repositorio,
        protected EscolhaElementoRepository $escolhas,
    ) {
        parent::__construct($repositorio);
    }

    /** @param array<string, mixed>|DTO $dados */
    public function criarParaUsuario(int $usuarioId, array|DTO $dados): Model
    {
        $dados = $this->comDadosDoSantuario($this->paraArray($dados), null);

        return DB::transaction(function () use ($usuarioId, $dados) {
            if ($this->ativo($dados['status'] ?? null)) {
                $this->garantirUnicoAtivo($usuarioId);
            }

            /** @var Dragao $dragao */
            $dragao = parent::criarParaUsuario($usuarioId, $dados);
            $this->sincronizarDragaoAtivo($usuarioId, $dragao);
            $this->registrarTrajetoria($dragao, null);

            return $dragao;
        });
    }

    /** @param array<string, mixed>|DTO $dados */
    public function atualizarDoUsuario(int $usuarioId, int|string $id, array|DTO $dados): Model
    {
        $dados = $this->paraArray($dados);

        return DB::transaction(function () use ($usuarioId, $id, $dados) {
            if ($this->ativo($dados['status'] ?? null)) {
                $this->garantirUnicoAtivo($usuarioId, (int) $id);
            }

            /** @var Dragao $atual */
            $atual = $this->buscarDoUsuario($usuarioId, $id);
            $dados = $this->comDadosDoSantuario($dados, $atual);
            $this->garantirElementoAposEscolha($dados, $atual);
            $elementoAnterior = $atual->elemento;

            /** @var Dragao $dragao */
            $dragao = parent::atualizarDoUsuario($usuarioId, $id, $dados);
            $this->sincronizarDragaoAtivo($usuarioId, $dragao);
            $this->registrarTrajetoria($dragao, $elementoAnterior);

            return $dragao;
        });
    }

    /**
     * O Santuário é permanente (MOD6-TASK19): um dragão do Santuário não pode
     * ser excluído pela API. Um dragão ativo continua podendo ser excluído.
     *
     * @throws RegraDeNegocioException respondida como 422
     */
    public function excluirDoUsuario(int $usuarioId, int|string $id): bool
    {
        /** @var Dragao $dragao */
        $dragao = $this->buscarDoUsuario($usuarioId, $id);

        if ($dragao->status === StatusDragao::SANTUARIO) {
            throw new RegraDeNegocioException('id', 'O dragão está no Santuário e não pode ser excluído.');
        }

        return $this->repositorio->excluir($dragao);
    }

    /**
     * Santuário do usuário (MOD6-TASK18): os dragões que concluíram o ciclo,
     * do término mais recente para o mais antigo, com a trajetória de elementos.
     *
     * @return Collection<int, Dragao>
     */
    public function santuario(int $usuarioId): Collection
    {
        return $this->repositorio->doSantuario($usuarioId);
    }

    /** Um dragão do Santuário do usuário; um dragão ativo ou de outro usuário devolve 404. */
    public function buscarNoSantuario(int $usuarioId, int|string $id): Dragao
    {
        return $this->repositorio->buscarDoSantuarioOuFalhar($usuarioId, $id);
    }

    /**
     * Ao entrar no Santuário, o dragão recebe a data de término (hoje, se não
     * foi enviada) e o tempo total do ciclo, sempre gerado pelas datas de
     * nascimento e de término: um tempo_total_dias enviado é descartado.
     * Ao ser reativado, o dragão perde a data de término e o tempo total.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     *
     * @throws RegraDeNegocioException 422 se o término for anterior ao nascimento
     */
    private function comDadosDoSantuario(array $dados, ?Dragao $atual): array
    {
        $noSantuario = array_key_exists('status', $dados)
            ? ! $this->ativo($dados['status'])
            : $atual?->status === StatusDragao::SANTUARIO;

        if (! $noSantuario) {
            if ($atual?->status === StatusDragao::SANTUARIO) {
                $dados['data_termino'] = null;
                $dados['tempo_total_dias'] = null;
            }

            return $dados;
        }

        $nascimento = $dados['data_nascimento'] ?? $atual?->data_nascimento;

        if ($nascimento === null) {
            return $dados;
        }

        $nascimento = CarbonImmutable::parse($nascimento)->startOfDay();
        $termino = CarbonImmutable::parse($dados['data_termino'] ?? $atual->data_termino ?? CarbonImmutable::today())->startOfDay();

        if ($termino->lt($nascimento)) {
            throw new RegraDeNegocioException('data_termino', 'A data de término não pode ser anterior à data de nascimento do dragão ('.$nascimento->toDateString().').');
        }

        $dados['data_termino'] = $termino->toDateString();
        $dados['tempo_total_dias'] = (int) $nascimento->diffInDays($termino);

        return $dados;
    }

    /**
     * A partir dos 6.000 XP do ciclo (EstagioDragao::XP_ESCOLHA_ELEMENTO), o
     * dragão precisa de um elemento: um PUT/PATCH que deixe o dragão com esse XP
     * e sem elemento devolve 422 em elemento.
     *
     * @param  array<string, mixed>  $dados
     *
     * @throws RegraDeNegocioException
     */
    private function garantirElementoAposEscolha(array $dados, Dragao $atual): void
    {
        $xpCiclo = (int) ($dados['xp_ciclo'] ?? $atual->xp_ciclo);
        $elemento = array_key_exists('elemento', $dados) ? $dados['elemento'] : $atual->elemento;

        if ($xpCiclo >= EstagioDragao::XP_ESCOLHA_ELEMENTO && $elemento === null) {
            throw new RegraDeNegocioException(
                'elemento',
                'O dragão chegou aos '.EstagioDragao::XP_ESCOLHA_ELEMENTO.' XP do ciclo: escolha o elemento (FOGO, TERRA, AGUA ou AR).',
            );
        }
    }

    /**
     * Registra a trajetória de elementos (MOD6-TASK11): quando o elemento do
     * dragão muda, grava uma escolha_elemento com o estágio em que ele estava.
     */
    private function registrarTrajetoria(Dragao $dragao, ?ElementoDragao $elementoAnterior): void
    {
        if ($dragao->elemento === null || $dragao->elemento === $elementoAnterior) {
            return;
        }

        $this->escolhas->criar([
            'fk_id_dragao' => $dragao->id,
            'elemento' => $dragao->elemento->value,
            'estagio' => $dragao->estagio->value,
            'data_escolha' => now(),
        ]);
    }

    /**
     * @throws RegraDeNegocioException respondida como 422
     */
    private function garantirUnicoAtivo(int $usuarioId, ?int $excetoDragaoId = null): void
    {
        if ($this->repositorio->usuarioTemDragaoAtivo($usuarioId, $excetoDragaoId)) {
            throw new RegraDeNegocioException('status', 'O usuário já tem um dragão ativo: só um dragão pode ficar ATIVO por vez.');
        }
    }

    /** Aponta users.fk_id_dragao para o dragão, se ele estiver ativo, ou o limpa, se ele deixou de estar. */
    private function sincronizarDragaoAtivo(int $usuarioId, Dragao $dragao): void
    {
        if ($dragao->status === StatusDragao::ATIVO) {
            $this->repositorio->definirDragaoAtivo($usuarioId, $dragao->id);
        } elseif ($dragao->usuario->fk_id_dragao === $dragao->id) {
            $this->repositorio->definirDragaoAtivo($usuarioId, null);
        }
    }

    /**
     * O estágio é sempre gerado pelo xp_ciclo (EstagioDragao::doXp): um estágio
     * enviado é descartado. Vale para todas as operações de criação e edição.
     */
    protected function paraArray(array|DTO $dados): array
    {
        $dados = parent::paraArray($dados);
        unset($dados['estagio']);

        if (isset($dados['xp_ciclo'])) {
            $dados['estagio'] = EstagioDragao::doXp((int) $dados['xp_ciclo'])->value;
        }

        return $dados;
    }

    private function ativo(mixed $status): bool
    {
        return ($status instanceof StatusDragao ? $status->value : $status) === StatusDragao::ATIVO->value;
    }
}
