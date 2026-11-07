<?php

namespace App\DTOs\Dragon;

use App\DTOs\DTO;
use App\Enums\ElementoDragao;
use Carbon\CarbonImmutable;

final readonly class EscolhaElementoDTO extends DTO
{
    public function __construct(
        public ?int $dragaoId = null,
        public ?ElementoDragao $elemento = null,
        public ?CarbonImmutable $dataEscolha = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            dragaoId: self::inteiro($dados, 'fk_id_dragao'),
            elemento: self::enum($dados, 'elemento', ElementoDragao::class),
            dataEscolha: self::data($dados, 'data_escolha'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'fk_id_dragao' => $this->dragaoId,
            'elemento' => $this->elemento?->value,
            'data_escolha' => $this->dataEscolha?->toDateTimeString(),
        ]);
    }
}
