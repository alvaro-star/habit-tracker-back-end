<?php

namespace App\DTOs\Auth;

use App\DTOs\DTO;

final readonly class CadastroDTO extends DTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
    ) {}

    /** @param array<string, mixed> $dados */
    public static function fromArray(array $dados): self
    {
        return new self(
            name: self::texto($dados, 'name'),
            email: self::texto($dados, 'email'),
            password: self::texto($dados, 'password'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ]);
    }
}
