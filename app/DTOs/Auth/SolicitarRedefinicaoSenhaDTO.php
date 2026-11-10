<?php

namespace App\DTOs\Auth;

use App\DTOs\DTO;

final readonly class SolicitarRedefinicaoSenhaDTO extends DTO
{
    public function __construct(
        public ?string $email = null,
    ) {}

    /** @param array<string, mixed> $dados */
    public static function fromArray(array $dados): self
    {
        return new self(
            email: self::texto($dados, 'email'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'email' => $this->email,
        ]);
    }
}
