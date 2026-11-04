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

    /** @return Collection<int, Model> */
    public function todos(): Collection
    {
        return $this->consulta()->get();
    }

    public function buscarOuFalhar(int|string $id): Model
    {
        return $this->consulta()->findOrFail($id);
    }

    /** @param array<string, mixed> $dados */
    public function criar(array $dados): Model
    {
        return $this->consulta()->create($dados);
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
