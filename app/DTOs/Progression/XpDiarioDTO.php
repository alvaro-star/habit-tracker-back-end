<?php

namespace App\DTOs\Progression;

use App\DTOs\DTO;
use Carbon\CarbonImmutable;

final readonly class XpDiarioDTO extends DTO
{
    public function __construct(
        public ?int $dragaoId = null,
        public ?CarbonImmutable $data = null,
        public ?float $percentualPonderado = null,
        public ?int $xpGanho = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            dragaoId: self::inteiro($dados, 'fk_id_dragao'),
            data: self::data($dados, 'data'),
            percentualPonderado: self::decimal($dados, 'percentual_ponderado'),
            xpGanho: self::inteiro($dados, 'xp_ganho'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'fk_id_dragao' => $this->dragaoId,
            'data' => $this->data?->toDateString(),
            'percentual_ponderado' => $this->percentualPonderado,
            'xp_ganho' => $this->xpGanho,
        ]);
    }
}
