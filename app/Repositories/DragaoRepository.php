<?php

namespace App\Repositories;

use App\Enums\EstagioDragao;
use App\Enums\StatusDragao;
use App\Models\Dragao;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** @extends BaseRepository<Dragao> */
class DragaoRepository extends BaseRepository
{
    protected string $model = Dragao::class;

    /**
     * Dragões do Santuário do usuário, do término mais recente para o mais
     * antigo, com a trajetória de elementos na ordem em que foram escolhidos.
     *
     * @return Collection<int, Dragao>
     */
    public function doSantuario(int $usuarioId): Collection
    {
        return $this->consultaDoSantuario($usuarioId)->get();
    }

    public function buscarDoSantuarioOuFalhar(int $usuarioId, int|string $id): Dragao
    {
        return $this->consultaDoSantuario($usuarioId)->findOrFail($id);
    }

    /** @return Builder<Dragao> */
    private function consultaDoSantuario(int $usuarioId): Builder
    {
        return $this->consultaDoUsuario($usuarioId)
            ->where('status', StatusDragao::SANTUARIO->value)
            ->with(['escolhasElemento' => fn ($escolhas) => $escolhas->orderBy('data_escolha')->orderBy('id')])
            ->orderByDesc('data_termino')
            ->orderByDesc('id');
    }

    /** Diz se o usuário já tem um dragão ATIVO, ignorando o dragão informado. */
    public function usuarioTemDragaoAtivo(int $usuarioId, ?int $excetoDragaoId = null): bool
    {
        return $this->consultaDoUsuario($usuarioId)
            ->where('status', StatusDragao::ATIVO->value)
            ->when($excetoDragaoId !== null, fn ($consulta) => $consulta->whereKeyNot($excetoDragaoId))
            ->exists();
    }

    /** Soma (ou subtrai, se negativa) a diferença ao xp_ciclo do dragão, sem deixá-lo abaixo de zero, e acerta o estágio. */
    public function somarXpCiclo(int $dragaoId, int $diferenca): void
    {
        /** @var Dragao $dragao */
        $dragao = $this->buscarOuFalhar($dragaoId);
        $xpCiclo = max(0, $dragao->xp_ciclo + $diferenca);

        $this->atualizar($dragao, ['xp_ciclo' => $xpCiclo, 'estagio' => EstagioDragao::doXp($xpCiclo)->value]);
    }

    /** Grava o dragão ativo do usuário em users.fk_id_dragao (null para nenhum). */
    public function definirDragaoAtivo(int $usuarioId, ?int $dragaoId): void
    {
        User::query()->whereKey($usuarioId)->update(['fk_id_dragao' => $dragaoId]);
    }
}
