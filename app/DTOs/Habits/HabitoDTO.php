<?php

namespace App\DTOs\Habits;

use App\DTOs\DTO;
use App\Enums\DiaSemana;
use App\Enums\Dificuldade;
use App\Enums\Frequencia;
use App\Enums\Polaridade;
use App\Enums\TipoMeta;

/**
 * O peso e a data de criação não fazem parte da entrada: o peso é gerado
 * pela dificuldade (HabitoService) e o created_at, pelo banco.
 */
final readonly class HabitoDTO extends DTO
{
    public function __construct(
        public ?int $categoriaId = null,
        public ?string $nome = null,
        public ?Polaridade $polaridade = null,
        public ?TipoMeta $tipoMeta = null,
        public ?Frequencia $frequencia = null,
        public int|DiaSemana|null $diaFrequencia = null,
        public ?float $valorMeta = null,
        public ?string $unidade = null,
        public ?Dificuldade $dificuldade = null,
        public ?bool $ativo = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            categoriaId: self::inteiro($dados, 'fk_id_categoria'),
            nome: self::texto($dados, 'nome'),
            polaridade: self::enum($dados, 'polaridade', Polaridade::class),
            tipoMeta: self::enum($dados, 'tipo_meta', TipoMeta::class),
            frequencia: self::enum($dados, 'frequencia', Frequencia::class),
            diaFrequencia: self::diaFrequencia($dados),
            valorMeta: self::decimal($dados, 'valor_meta'),
            unidade: self::texto($dados, 'unidade'),
            dificuldade: self::enum($dados, 'dificuldade', Dificuldade::class),
            ativo: self::booleano($dados, 'ativo'),
        );
    }

    /**
     * O dia do mês chega como número e o dia da semana, como texto (SEG a DOM).
     *
     * @param  array<string, mixed>  $dados
     */
    private static function diaFrequencia(array $dados): int|DiaSemana|null
    {
        if (! isset($dados['dia_frequencia'])) {
            return null;
        }

        return is_numeric($dados['dia_frequencia'])
            ? (int) $dados['dia_frequencia']
            : DiaSemana::from($dados['dia_frequencia']);
    }

    public function toArray(): array
    {
        return $this->semNulos([
            'fk_id_categoria' => $this->categoriaId,
            'nome' => $this->nome,
            'polaridade' => $this->polaridade?->value,
            'tipo_meta' => $this->tipoMeta?->value,
            'frequencia' => $this->frequencia?->value,
            'dia_frequencia' => $this->diaFrequencia instanceof DiaSemana ? $this->diaFrequencia->value : $this->diaFrequencia,
            'valor_meta' => $this->valorMeta,
            'unidade' => $this->unidade,
            'dificuldade' => $this->dificuldade?->value,
            'ativo' => $this->ativo,
        ]);
    }
}
