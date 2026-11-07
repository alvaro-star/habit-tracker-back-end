<?php

namespace App\Http\Requests\Notifications;

use App\DTOs\Notifications\GatilhoNotificacaoDTO;
use App\Enums\CanalNotificacao;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class GatilhoNotificacaoRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            'tipo' => [$obrigatorio, 'string', 'max:40'],
            'canal' => [$obrigatorio, Rule::enum(CanalNotificacao::class)],
            'frequencia' => [$obrigatorio, 'string', 'max:40'],
            'texto_base' => [$obrigatorio, 'string', 'max:255'],
        ];
    }

    public function toDto(): GatilhoNotificacaoDTO
    {
        return GatilhoNotificacaoDTO::fromArray($this->validated());
    }
}
