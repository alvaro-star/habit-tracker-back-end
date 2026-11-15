<?php

namespace App\Http\Resources\Onboarding;

use App\Http\Resources\Dragon\DragaoResource;
use App\Http\Resources\Habits\HabitoResource;
use App\Http\Resources\Library\CategoriaResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class OnboardingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            // false no primeiro acesso: o front-end deve apresentar o fluxo de onboarding.
            'concluido' => (bool) $this->onboarding_concluido,
            'nome_usuario' => $this->name,
            'objetivos' => CategoriaResource::collection($this->objetivos),
            // Dragão ativo, com o ovo escolhido; null enquanto o onboarding não foi concluído.
            'dragao' => $this->dragaoAtivo !== null ? new DragaoResource($this->dragaoAtivo) : null,
            'habitos' => HabitoResource::collection($this->habitos),
        ];
    }
}
