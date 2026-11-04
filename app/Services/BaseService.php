<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseService
{
    public function __construct(protected BaseRepository $repositorio) {}

    /** @return Collection<int, Model> */
    public function listar(): Collection
    {
        return $this->repositorio->todos();
    }

    public function buscar(int|string $id): Model
    {
        return $this->repositorio->buscarOuFalhar($id);
    }

    /** @param array<string, mixed> $dados */
    public function criar(array $dados): Model
    {
        return $this->repositorio->criar($dados);
    }

    /** @param array<string, mixed> $dados */
    public function atualizar(int|string $id, array $dados): Model
    {
        return $this->repositorio->atualizar($this->repositorio->buscarOuFalhar($id), $dados);
    }

    public function excluir(int|string $id): bool
    {
        return $this->repositorio->excluir($this->repositorio->buscarOuFalhar($id));
    }
}
