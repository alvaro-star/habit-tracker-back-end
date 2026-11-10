<?php

namespace App\Http\Requests\Notifications;

use App\DTOs\Notifications\ConfigNotificacaoDTO;
use App\Http\Requests\ApiRequest;

class ConfigNotificacaoRequest extends ApiRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            /** Se o usuário quer receber este gatilho. */
            'ativo' => ['required', 'boolean'],
            /**
             * Horário (HH:MM, em UTC) a partir do qual o aviso de streak em risco é enviado.
             * Ausente ou null: usa o horário padrão. Não se aplica aos avisos de inatividade.
             */
            'horario' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function toDto(): ConfigNotificacaoDTO
    {
        return ConfigNotificacaoDTO::fromArray($this->validated());
    }
}
