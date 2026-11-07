<?php

namespace App\DTOs\Dragon;

use App\DTOs\DTO;
use Carbon\CarbonImmutable;

final readonly class EscolhaElementoDTO extends DTO
{
    public function __construct(
        public ?int $dragaoId = null,
        public ?int $elementoId = null,
        public ?int $estagioId = null,
        public ?CarbonImmutable $dataEscolha = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            dragaoId: self::inteiro($dados, 'fk_id_dragao'),
            elementoId: self::inteiro($dados, 'fk_id_elemento'),
            estagioId: self::inteiro($dados, 'fk_id_estagio'),
            dataEscolha: self::data($dados, 'data_escolha'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'fk_id_dragao' => $this->dragaoId,
            'fk_id_elemento' => $this->elementoId,
            'fk_id_estagio' => $this->estagioId,
            'data_escolha' => $this->dataEscolha?->toDateTimeString(),
        ]);
    }
}
