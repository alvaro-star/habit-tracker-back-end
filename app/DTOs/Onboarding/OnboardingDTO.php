<?php

namespace App\DTOs\Onboarding;

use App\DTOs\DTO;

/**
 * Respostas do fluxo de onboarding (ESCOPO.md, seção 7.1), enviadas de uma vez
 * na finalização: ovo, nome do dragão, nome do usuário, objetivos e hábitos.
 */
final readonly class OnboardingDTO extends DTO
{
    /**
     * @param  array<int, int>  $objetivos  ids das categorias escolhidas como objetivo
     * @param  array<int, int>  $habitos  ids dos modelos de hábito escolhidos na biblioteca
     */
    public function __construct(
        public string $ovo,
        public string $nomeDragao,
        public string $nomeUsuario,
        public array $objetivos = [],
        public array $habitos = [],
    ) {}

    /** @param array<string, mixed> $dados dados validados da requisição */
    public static function fromArray(array $dados): self
    {
        return new self(
            ovo: (string) $dados['ovo'],
            nomeDragao: (string) $dados['nome_dragao'],
            nomeUsuario: (string) $dados['nome_usuario'],
            objetivos: self::ids($dados['objetivos'] ?? []),
            habitos: self::ids($dados['habitos'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'ovo' => $this->ovo,
            'nome_dragao' => $this->nomeDragao,
            'nome_usuario' => $this->nomeUsuario,
            'objetivos' => $this->objetivos,
            'habitos' => $this->habitos,
        ];
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, int> ids inteiros, sem repetição
     */
    private static function ids(array $ids): array
    {
        return array_values(array_unique(array_map(intval(...), $ids)));
    }
}
