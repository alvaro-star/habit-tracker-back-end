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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Um usuário pode ter vários dragões (os do Santuário), mas só um ATIVO.
 * O dragão ativo fica em users.fk_id_dragao, mantido por este service.
 *
 * @property DragaoRepository $repositorio
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
        $dados = $this->paraArray($dados);

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
