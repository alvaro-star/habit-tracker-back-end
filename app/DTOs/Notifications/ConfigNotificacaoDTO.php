<?php

namespace App\DTOs\Notifications;

use App\DTOs\DTO;

final readonly class ConfigNotificacaoDTO extends DTO
{
    public function __construct(
        public bool $ativo,
        public ?string $horario = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            ativo: (bool) self::booleano($dados, 'ativo'),
            horario: self::texto($dados, 'horario'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos(['ativo' => $this->ativo, 'horario' => $this->horario]);
    }
}
