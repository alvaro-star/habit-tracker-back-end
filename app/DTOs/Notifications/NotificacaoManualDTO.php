<?php

namespace App\DTOs\Notifications;

use App\DTOs\DTO;

final readonly class NotificacaoManualDTO extends DTO
{
    public function __construct(
        public ?int $fk_id_usuario,
        public string $titulo,
        public string $texto,
        public string $data,
        public string $horario,
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            fk_id_usuario: self::inteiro($dados, 'fk_id_usuario'),
            titulo: (string) self::texto($dados, 'titulo'),
            texto: (string) self::texto($dados, 'texto'),
            data: (string) self::texto($dados, 'data'),
            horario: (string) self::texto($dados, 'horario'),
        );
    }

    public function toArray(): array
    {
        return [
            'fk_id_usuario' => $this->fk_id_usuario,
            'titulo' => $this->titulo,
            'texto' => $this->texto,
            'data' => $this->data,
            'horario' => $this->horario,
        ];
    }
}
