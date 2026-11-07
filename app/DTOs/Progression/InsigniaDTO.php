<?php

namespace App\DTOs\Progression;

use App\DTOs\DTO;

final readonly class InsigniaDTO extends DTO
{
    public function __construct(
        public ?string $nome = null,
        public ?string $descricao = null,
        public ?string $iconeUrl = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            nome: self::texto($dados, 'nome'),
            descricao: self::texto($dados, 'descricao'),
            iconeUrl: self::texto($dados, 'icone_url'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'icone_url' => $this->iconeUrl,
        ]);
    }
}
