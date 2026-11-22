<?php

namespace App\Http\Requests\Notifications;

use App\DTOs\Notifications\NotificacaoManualDTO;
use App\Http\Requests\ApiRequest;
use App\Rules\DataValida;

class NotificacaoManualRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            /**
             * Id do usuário que vai receber a notificação. Enviar null manda para todos
             * os usuários, com um job por usuário. O campo é obrigatório no corpo, para
             * que esquecê-lo não dispare um envio geral.
             */
            'fk_id_usuario' => ['present', 'nullable', 'integer', 'exists:users,id'],
            /** Título do push. */
            'titulo' => ['required', 'string', 'max:100'],
            /** Conteúdo do push. */
            'texto' => ['required', 'string', 'max:255'],
            /** Dia do envio (AAAA-MM-DD), no fuso de Campo Grande. */
            'data' => ['required', 'string', new DataValida],
            /** Horário do envio (HH:MM), no fuso de Campo Grande. Não pode estar no passado. */
            'horario' => ['required', 'date_format:H:i'],
        ];
    }

    public function toDto(): NotificacaoManualDTO
    {
        return NotificacaoManualDTO::fromArray($this->validated());
    }
}
