<?php

namespace App\DTOs\Auth;

use App\DTOs\DTO;

final readonly class OnboardingDTO extends DTO
{
    public function __construct(
        public ?bool $concluido = null,
    ) {}

    /** @param array<string, mixed> $dados */
    public static function fromArray(array $dados): self
    {
        return new self(
            concluido: self::booleano($dados, 'concluido'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'onboarding_concluido' => $this->concluido,
        ]);
    }
}
