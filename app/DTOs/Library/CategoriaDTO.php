<?php

namespace App\DTOs\Library;

use App\DTOs\DTO;

final readonly class CategoriaDTO extends DTO
{
    public function __construct(
        public ?string $nome = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            nome: self::texto($dados, 'nome'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'nome' => $this->nome,
        ]);
    }
}
