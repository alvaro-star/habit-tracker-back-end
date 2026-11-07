<?php

namespace App\DTOs\CheckIns;

use App\DTOs\DTO;
use App\Enums\StatusRegistro;
use Carbon\CarbonImmutable;

final readonly class RegistroHabitoDTO extends DTO
{
    public function __construct(
        public ?int $habitoId = null,
        public ?CarbonImmutable $data = null,
        public ?StatusRegistro $status = null,
        public ?float $valorRealizado = null,
        public ?float $percentual = null,
        /** true marca o hábito no dia (cria ou atualiza o registro); false o desmarca (exclui o registro). */
        public ?bool $checked = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            habitoId: self::inteiro($dados, 'fk_id_habito'),
            data: self::data($dados, 'data'),
            status: self::enum($dados, 'status', StatusRegistro::class),
            valorRealizado: self::decimal($dados, 'valor_realizado'),
            percentual: self::decimal($dados, 'percentual'),
            checked: self::booleano($dados, 'checked'),
        );
    }

    /** O checked não é coluna: decide se o registro é gravado ou excluído. */
    public function toArray(): array
    {
        return $this->semNulos([
            'fk_id_habito' => $this->habitoId,
            'data' => $this->data?->toDateString(),
            'status' => $this->status?->value,
            'valor_realizado' => $this->valorRealizado,
            'percentual' => $this->percentual,
        ]);
    }
}
