<?php

namespace App\DTOs\Auth;

use App\DTOs\DTO;

final readonly class RedefinirSenhaDTO extends DTO
{
    public function __construct(
        public ?string $email = null,
        public ?string $token = null,
        public ?string $password = null,
    ) {}

    /** @param array<string, mixed> $dados */
    public static function fromArray(array $dados): self
    {
        return new self(
            email: self::texto($dados, 'email'),
            token: self::texto($dados, 'token'),
            password: self::texto($dados, 'password'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'email' => $this->email,
            'token' => $this->token,
            'password' => $this->password,
        ]);
    }
}
