<?php

namespace App\DTOs;

use BackedEnum;
use Carbon\CarbonImmutable;

/**
 * Objeto que representa os dados de entrada de uma requisição já validada.
 *
 * Cada DTO é criado pelo método toDto() da Form Request (que chama fromArray()
 * com os dados validados) e converte os dados de volta para as colunas do banco
 * em toArray(). Os campos não informados ficam null e são descartados no
 * toArray(), o que permite atualizações parciais (PATCH).
 */
abstract readonly class DTO
{
    /** @return array<string, mixed> colunas do banco, só com os campos informados */
    abstract public function toArray(): array;

    /**
     * @param  array<string, mixed>  $colunas
     * @return array<string, mixed>
     */
    protected function semNulos(array $colunas): array
    {
        return array_filter($colunas, fn ($valor) => $valor !== null);
    }

    /** @param array<string, mixed> $dados */
    protected static function inteiro(array $dados, string $campo): ?int
    {
        return isset($dados[$campo]) ? (int) $dados[$campo] : null;
    }

    /** @param array<string, mixed> $dados */
    protected static function decimal(array $dados, string $campo): ?float
    {
        return isset($dados[$campo]) ? (float) $dados[$campo] : null;
    }

    /** @param array<string, mixed> $dados */
    protected static function texto(array $dados, string $campo): ?string
    {
        return isset($dados[$campo]) ? (string) $dados[$campo] : null;
    }

    /** @param array<string, mixed> $dados */
    protected static function booleano(array $dados, string $campo): ?bool
    {
        return isset($dados[$campo]) ? filter_var($dados[$campo], FILTER_VALIDATE_BOOLEAN) : null;
    }

    /** @param array<string, mixed> $dados */
    protected static function data(array $dados, string $campo): ?CarbonImmutable
    {
        return isset($dados[$campo]) ? CarbonImmutable::parse($dados[$campo]) : null;
    }

    /**
     * @template T of BackedEnum
     *
     * @param  array<string, mixed>  $dados
     * @param  class-string<T>  $enum
     * @return T|null
     */
    protected static function enum(array $dados, string $campo, string $enum): ?BackedEnum
    {
        return isset($dados[$campo]) ? $enum::from($dados[$campo]) : null;
    }
}
