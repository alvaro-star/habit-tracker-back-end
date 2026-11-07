<?php

namespace App\DTOs\Progression;

use App\DTOs\DTO;

final readonly class NivelDTO extends DTO
{
    public function __construct(
        public ?int $xpNecessario = null,
        public ?int $xpAcumulado = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            xpNecessario: self::inteiro($dados, 'xp_necessario'),
            xpAcumulado: self::inteiro($dados, 'xp_acumulado'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'xp_necessario' => $this->xpNecessario,
            'xp_acumulado' => $this->xpAcumulado,
        ]);
    }
}
