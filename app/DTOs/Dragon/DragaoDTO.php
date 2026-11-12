<?php

namespace App\DTOs\Dragon;

use App\DTOs\DTO;
use App\Enums\ElementoDragao;
use App\Enums\StatusDragao;
use Carbon\CarbonImmutable;

final readonly class DragaoDTO extends DTO
{
    public function __construct(
        public ?ElementoDragao $elemento = null,
        public ?string $nome = null,
        public ?int $xpCiclo = null,
        public ?StatusDragao $status = null,
        public ?CarbonImmutable $dataNascimento = null,
        public ?CarbonImmutable $dataTermino = null,
        public ?string $arteFinalUrl = null,
        public ?int $tempoTotalDias = null,
        public ?string $ovo = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            elemento: self::enum($dados, 'elemento', ElementoDragao::class),
            nome: self::texto($dados, 'nome'),
            xpCiclo: self::inteiro($dados, 'xp_ciclo'),
            status: self::enum($dados, 'status', StatusDragao::class),
            dataNascimento: self::data($dados, 'data_nascimento'),
            dataTermino: self::data($dados, 'data_termino'),
            arteFinalUrl: self::texto($dados, 'arte_final_url'),
            tempoTotalDias: self::inteiro($dados, 'tempo_total_dias'),
            ovo: self::texto($dados, 'ovo'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'elemento' => $this->elemento?->value,
            'nome' => $this->nome,
            'ovo' => $this->ovo,
            'xp_ciclo' => $this->xpCiclo,
            'status' => $this->status?->value,
            'data_nascimento' => $this->dataNascimento?->toDateString(),
            'data_termino' => $this->dataTermino?->toDateString(),
            'arte_final_url' => $this->arteFinalUrl,
            'tempo_total_dias' => $this->tempoTotalDias,
        ]);
    }
}
