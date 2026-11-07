<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Violação de uma regra de negócio verificada no service, que a Form Request
 * não consegue validar sozinha (ex.: depende do registro já salvo).
 * O bootstrap/app.php a converte no status informado (422 por padrão; ex.: 409
 * para um conflito), no mesmo formato dos erros de validação.
 */
class RegraDeNegocioException extends RuntimeException
{
    public function __construct(
        public readonly string $campo,
        string $mensagem,
        public readonly int $status = 422,
    ) {
        parent::__construct($mensagem);
    }
}
