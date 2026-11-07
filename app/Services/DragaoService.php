<?php

namespace App\Services;

use App\DTOs\DTO;
use App\Enums\StatusDragao;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Dragao;
use App\Repositories\DragaoRepository;
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
    public function __construct(DragaoRepository $repositorio)
    {
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

            /** @var Dragao $dragao */
            $dragao = parent::atualizarDoUsuario($usuarioId, $id, $dados);
            $this->sincronizarDragaoAtivo($usuarioId, $dragao);

            return $dragao;
        });
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

    private function ativo(mixed $status): bool
    {
        return ($status instanceof StatusDragao ? $status->value : $status) === StatusDragao::ATIVO->value;
    }
}
