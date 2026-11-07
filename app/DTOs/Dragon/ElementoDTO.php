<?php

namespace App\DTOs\Dragon;

use App\DTOs\DTO;

final readonly class ElementoDTO extends DTO
{
    public function __construct(
        public ?string $nome = null,
        public ?string $cenarioUrl = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            nome: self::texto($dados, 'nome'),
            cenarioUrl: self::texto($dados, 'cenario_url'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'nome' => $this->nome,
            'cenario_url' => $this->cenarioUrl,
        ]);
    }
}
