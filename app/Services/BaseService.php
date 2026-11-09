<?php

namespace App\Services;

use App\DTOs\DTO;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** @template TModel of Model */
abstract class BaseService
{
    /** @param BaseRepository<TModel> $repositorio */
    public function __construct(protected BaseRepository $repositorio) {}

    /** @return Collection<int, TModel> */
    public function listar(): Collection
    {
        return $this->repositorio->todos();
    }

    /** @return TModel */
    public function buscar(int|string $id): Model
    {
        return $this->repositorio->buscarOuFalhar($id);
    }

    /**
     * @param  array<string, mixed>|DTO  $dados
     * @return TModel
     */
    public function criar(array|DTO $dados): Model
    {
        return $this->repositorio->criar($this->paraArray($dados));
    }

    /**
     * @param  array<string, mixed>|DTO  $dados
     * @return TModel
     */
    public function atualizar(int|string $id, array|DTO $dados): Model
    {
        return $this->repositorio->atualizar($this->repositorio->buscarOuFalhar($id), $this->paraArray($dados));
    }

    public function excluir(int|string $id): bool
    {
        return $this->repositorio->excluir($this->repositorio->buscarOuFalhar($id));
    }

    // Dados pessoais: as operações abaixo só enxergam os registros do usuário.

    /** @return Collection<int, TModel> */
    public function listarDoUsuario(int $usuarioId): Collection
    {
        return $this->repositorio->doUsuario($usuarioId);
    }

    /** @return TModel */
    public function buscarDoUsuario(int $usuarioId, int|string $id): Model
    {
        return $this->repositorio->buscarDoUsuarioOuFalhar($usuarioId, $id);
    }

    /**
     * Cria um registro já vinculado ao usuário (coluna fk_id_usuario).
     *
     * @param  array<string, mixed>|DTO  $dados
     * @return TModel
     */
    public function criarParaUsuario(int $usuarioId, array|DTO $dados): Model
    {
        return $this->repositorio->criar([...$this->paraArray($dados), 'fk_id_usuario' => $usuarioId]);
    }

    /**
     * @param  array<string, mixed>|DTO  $dados
     * @return TModel
     */
    public function atualizarDoUsuario(int $usuarioId, int|string $id, array|DTO $dados): Model
    {
        return $this->repositorio->atualizar($this->buscarDoUsuario($usuarioId, $id), $this->paraArray($dados));
    }

    public function excluirDoUsuario(int $usuarioId, int|string $id): bool
    {
        return $this->repositorio->excluir($this->buscarDoUsuario($usuarioId, $id));
    }

    /**
     * @param  array<string, mixed>|DTO  $dados
     * @return array<string, mixed>
     */
    protected function paraArray(array|DTO $dados): array
    {
        return $dados instanceof DTO ? $dados->toArray() : $dados;
    }
}
