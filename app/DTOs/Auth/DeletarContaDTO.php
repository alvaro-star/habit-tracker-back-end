<?php

namespace App\DTOs\Auth;

use App\DTOs\DTO;

final readonly class DeletarContaDTO extends DTO
{
    public function __construct(
        public ?string $password = null,
    ) {}

    /** @param array<string, mixed> $dados */
    public static function fromArray(array $dados): self
    {
        return new self(
            password: self::texto($dados, 'password'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'password' => $this->password,
        ]);
    }
 }