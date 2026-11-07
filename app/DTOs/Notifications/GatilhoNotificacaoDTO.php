<?php

namespace App\DTOs\Notifications;

use App\DTOs\DTO;
use App\Enums\CanalNotificacao;

final readonly class GatilhoNotificacaoDTO extends DTO
{
    public function __construct(
        public ?string $tipo = null,
        public ?CanalNotificacao $canal = null,
        public ?string $frequencia = null,
        public ?string $textoBase = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            tipo: self::texto($dados, 'tipo'),
            canal: self::enum($dados, 'canal', CanalNotificacao::class),
            frequencia: self::texto($dados, 'frequencia'),
            textoBase: self::texto($dados, 'texto_base'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'tipo' => $this->tipo,
            'canal' => $this->canal?->value,
            'frequencia' => $this->frequencia,
            'texto_base' => $this->textoBase,
        ]);
    }
}
