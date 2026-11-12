<?php

namespace App\Http\Requests\Onboarding;

use App\DTOs\Onboarding\OnboardingDTO;
use App\Http\Requests\ApiRequest;

class OnboardingRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            /** Ovo escolhido: identificador definido pelo front-end (ex.: `ovo_azul`). Fica gravado no dragão. */
            'ovo' => ['required', 'string', 'max:40'],
            /** Nome do dragão que nasce do ovo. */
            'nome_dragao' => ['required', 'string', 'max:40'],
            /** Nome do usuário, gravado no perfil (`name`). */
            'nome_usuario' => ['required', 'string', 'max:255'],
            /** Objetivos: ids das categorias que o usuário quer priorizar. Pode ser uma lista vazia. */
            'objetivos' => ['present', 'array'],
            'objetivos.*' => ['integer', 'distinct', 'exists:categoria,id'],
            /**
             * Hábitos iniciais: ids dos modelos da biblioteca (`/api/library/habit-templates`).
             * Cada modelo vira um hábito diário do usuário, com a meta, a unidade e o peso de
             * referência da dificuldade do modelo. Pode ser uma lista vazia.
             */
            'habitos' => ['present', 'array'],
            'habitos.*' => ['integer', 'distinct', 'exists:habito_modelo,id'],
        ];
    }

    public function toDto(): OnboardingDTO
    {
        return OnboardingDTO::fromArray($this->validated());
    }
}
