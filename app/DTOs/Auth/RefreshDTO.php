<?php

namespace App\DTOs\Auth;

use App\DTOs\DTO;

final readonly class RefreshDTO extends DTO
{
    public function __construct(
        public ?string $token_dispositivo = null,
    ) {}

    /** @param array<string, mixed> $dados */
    public static function fromArray(array $dados): self
    {
        return new self(
            token_dispositivo: self::texto($dados, 'token_dispositivo'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'token_dispositivo' => $this->token_dispositivo,
        ]);
    }
}
