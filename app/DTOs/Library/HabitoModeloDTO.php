<?php

namespace App\DTOs\Library;

use App\DTOs\DTO;
use App\Enums\Dificuldade;
use App\Enums\TipoMeta;

final readonly class HabitoModeloDTO extends DTO
{
    public function __construct(
        public ?int $categoriaId = null,
        public ?string $nome = null,
        public ?TipoMeta $tipoMeta = null,
        public ?float $metaPadrao = null,
        public ?string $unidadePadrao = null,
        public ?Dificuldade $dificuldadePadrao = null,
        public ?bool $prioridadeMvp = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            categoriaId: self::inteiro($dados, 'fk_id_categoria'),
            nome: self::texto($dados, 'nome'),
            tipoMeta: self::enum($dados, 'tipo_meta', TipoMeta::class),
            metaPadrao: self::decimal($dados, 'meta_padrao'),
            unidadePadrao: self::texto($dados, 'unidade_padrao'),
            dificuldadePadrao: self::enum($dados, 'dificuldade_padrao', Dificuldade::class),
            prioridadeMvp: self::booleano($dados, 'prioridade_mvp'),
        );
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'fk_id_categoria' => $this->categoriaId,
            'nome' => $this->nome,
            'tipo_meta' => $this->tipoMeta?->value,
            'meta_padrao' => $this->metaPadrao,
            'unidade_padrao' => $this->unidadePadrao,
            'dificuldade_padrao' => $this->dificuldadePadrao?->value,
            'prioridade_mvp' => $this->prioridadeMvp,
        ]);
    }
}
