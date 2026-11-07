<?php

namespace App\DTOs\Dragon;

use App\DTOs\DTO;

final readonly class EstagioDTO extends DTO
{
    public function __construct(
        public ?string $nome = null,
        public ?int $ordem = null,
        public ?int $xpMarco = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            nome: self::texto($dados, 'nome'),
            ordem: self::inteiro($dados, 'ordem'),
            xpMarco: self::inteiro($dados, 'xp_marco'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'nome' => $this->nome,
            'ordem' => $this->ordem,
            'xp_marco' => $this->xpMarco,
        ]);
    }
}
