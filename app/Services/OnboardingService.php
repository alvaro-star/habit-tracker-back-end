<?php

namespace App\Services;

use App\DTOs\Onboarding\OnboardingDTO;
use App\Enums\Frequencia;
use App\Enums\Polaridade;
use App\Enums\StatusDragao;
use App\Exceptions\RegraDeNegocioException;
use App\Models\HabitoModelo;
use App\Models\User;
use App\Repositories\HabitoModeloRepository;
use App\Repositories\UsuarioRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Onboarding do primeiro acesso (MOD8-TASK1 a TASK3; ESCOPO.md, seção 7.1).
 * O front-end conduz as 8 etapas e envia as respostas de uma vez; este service
 * grava tudo numa transação, usando os services dos outros módulos.
 */
class OnboardingService
{
    public function __construct(
        protected UsuarioRepository $usuarios,
        protected HabitoModeloRepository $modelos,
        protected DragaoService $dragoes,
        protected HabitoService $habitos,
    ) {}

    /** Situação do onboarding do usuário, com o que já foi configurado. */
    public function situacao(User $usuario): User
    {
        return $usuario->load(['objetivos', 'dragaoAtivo', 'habitos']);
    }

    /**
     * Conclui o onboarding: grava o nome e os objetivos do usuário, cria o
     * dragão ativo com o ovo escolhido (nascido hoje, com 0 XP) e um hábito
     * para cada modelo da biblioteca. Só pode ser feito uma vez.
     *
     * @throws RegraDeNegocioException 409 se o onboarding já foi concluído
     */
    public function concluir(User $usuario, OnboardingDTO $dados): User
    {
        if ($usuario->onboarding_concluido) {
            throw new RegraDeNegocioException('onboarding', 'O onboarding já foi concluído.', 409);
        }

        DB::transaction(function () use ($usuario, $dados) {
            $this->dragoes->criarParaUsuario($usuario->id, [
                'nome' => $dados->nomeDragao,
                'ovo' => $dados->ovo,
                'xp_ciclo' => 0,
                'status' => StatusDragao::ATIVO->value,
                'data_nascimento' => CarbonImmutable::today()->toDateString(),
            ]);

            foreach ($dados->habitos as $modeloId) {
                /** @var HabitoModelo $modelo */
                $modelo = $this->modelos->buscarOuFalhar($modeloId);
                $this->habitos->criarParaUsuario($usuario->id, $this->habitoDoModelo($modelo));
            }

            $this->usuarios->concluirOnboarding($usuario, $dados->nomeUsuario, $dados->objetivos);
        });

        return $this->situacao($usuario->refresh());
    }

    /**
     * Hábito inicial criado a partir de um modelo da biblioteca: diário, bom,
     * com a meta e a unidade padrão e o peso de referência da dificuldade.
     *
     * @return array<string, mixed>
     */
    private function habitoDoModelo(HabitoModelo $modelo): array
    {
        return [
            'fk_id_categoria' => $modelo->fk_id_categoria,
            'nome' => $modelo->nome,
            'polaridade' => Polaridade::BOM->value,
            'tipo_meta' => $modelo->tipo_meta->value,
            'frequencia' => Frequencia::DIARIA->value,
            'dia_frequencia' => null,
            'valor_meta' => $modelo->meta_padrao,
            'unidade' => $modelo->unidade_padrao,
            'peso' => $modelo->dificuldade_padrao->peso(),
        ];
    }
}
