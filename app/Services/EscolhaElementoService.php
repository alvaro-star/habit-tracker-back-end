<?php

namespace App\Services;

use App\DTOs\DTO;
use App\Models\Dragao;
use App\Models\EscolhaElemento;
use App\Repositories\DragaoRepository;
use App\Repositories\EscolhaElementoRepository;
use Illuminate\Database\Eloquent\Model;

/** @extends BaseService<EscolhaElemento> */
class EscolhaElementoService extends BaseService
{
    public function __construct(
        EscolhaElementoRepository $repositorio,
        protected DragaoRepository $dragoes,
    ) {
        parent::__construct($repositorio);
    }

    /**
     * A escolha guarda o estágio em que o dragão estava no momento dela, que
     * não vem da requisição.
     *
     * @param  array<string, mixed>|DTO  $dados
     */
    public function criar(array|DTO $dados): Model
    {
        $dados = $this->paraArray($dados);

        /** @var Dragao $dragao */
        $dragao = $this->dragoes->buscarOuFalhar($dados['fk_id_dragao']);

        return parent::criar([...$dados, 'estagio' => $dragao->estagio->value]);
    }
}
