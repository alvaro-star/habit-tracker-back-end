<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    /** @var class-string<Model> */
    protected string $model;

    protected function consulta(): Builder
    {
        return $this->model::query();
    }

    /**
     * Consulta restrita aos registros de um usuário. O padrão filtra pela
     * coluna fk_id_usuario; repositories de tabelas sem essa coluna
     * sobrescrevem o método para chegar ao dono por um relacionamento.
     */
    protected function consultaDoUsuario(int $usuarioId): Builder
    {
        return $this->consulta()->where('fk_id_usuario', $usuarioId);
    }

    /** @return Collection<int, Model> */
    public function todos(): Collection
    {
        return $this->consulta()->get();
    }

    public function buscarOuFalhar(int|string $id): Model
    {
        return $this->consulta()->findOrFail($id);
    }

    /** @return Collection<int, Model> */
    public function doUsuario(int $usuarioId): Collection
    {
        return $this->consultaDoUsuario($usuarioId)->get();
    }

    public function buscarDoUsuarioOuFalhar(int $usuarioId, int|string $id): Model
    {
        return $this->consultaDoUsuario($usuarioId)->findOrFail($id);
    }

    /** @param array<string, mixed> $dados */
    public function criar(array $dados): Model
    {
        // refresh() traz os defaults do banco (ex.: percentual, ativo) que o create não devolve.
        return $this->consulta()->create($dados)->refresh();
    }

    /** @param array<string, mixed> $dados */
    public function atualizar(Model $model, array $dados): Model
    {
        $model->update($dados);

        return $model->refresh();
    }

    public function excluir(Model $model): bool
    {
        return (bool) $model->delete();
    }
}
