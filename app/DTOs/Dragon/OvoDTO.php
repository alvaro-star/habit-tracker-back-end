<?php

namespace App\DTOs\Dragon;

use App\DTOs\DTO;

final readonly class OvoDTO extends DTO
{
    public function __construct(
        public ?string $nome = null,
        public ?string $imagemUrl = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            nome: self::texto($dados, 'nome'),
            imagemUrl: self::texto($dados, 'imagem_url'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'nome' => $this->nome,
            'imagem_url' => $this->imagemUrl,
        ]);
    }
}
