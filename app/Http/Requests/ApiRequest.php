<?php

namespace App\Http\Requests;

use App\DTOs\DTO;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base das Form Requests da API. A mesma request atende criação e edição:
 * no POST os campos obrigatórios usam `required`, e no PUT/PATCH usam
 * `sometimes`, para aceitar atualizações parciais.
 */
abstract class ApiRequest extends FormRequest
{
    /** Cria o DTO a partir dos dados validados. */
    abstract public function toDto(): DTO;

    protected function obrigatorio(): string
    {
        return $this->isMethod('POST') ? 'required' : 'sometimes';
    }
}
