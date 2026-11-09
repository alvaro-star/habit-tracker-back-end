<?php

namespace App\Services;

use App\DTOs\Notifications\ConfigNotificacaoDTO;
use App\Models\ConfigNotificacao;
use App\Models\GatilhoNotificacao;
use App\Repositories\ConfigNotificacaoRepository;
use App\Repositories\GatilhoNotificacaoRepository;
use Illuminate\Support\Collection;

class ConfigNotificacaoService
{
    public function __construct(
        protected ConfigNotificacaoRepository $configuracoes,
        protected GatilhoNotificacaoRepository $gatilhos,
    ) {}

    /**
     * Uma configuração por gatilho do catálogo. Gatilho sem configuração
     * gravada vem ativo e sem horário, que é o padrão do envio.
     *
     * @return Collection<int, ConfigNotificacao>
     */
    public function doUsuario(int $usuarioId): Collection
    {
        $salvas = $this->configuracoes->doUsuario($usuarioId)->keyBy('fk_id_gatilho');

        return $this->gatilhos->todos()->toBase()->map(
            fn (GatilhoNotificacao $gatilho) => $this->comGatilho($salvas->get($gatilho->id), $usuarioId, $gatilho),
        );
    }

    /** Substitui a configuração do usuário no gatilho; gatilho inexistente devolve 404. */
    public function salvar(int $usuarioId, int $gatilhoId, ConfigNotificacaoDTO $dto): ConfigNotificacao
    {
        /** @var GatilhoNotificacao $gatilho */
        $gatilho = $this->gatilhos->buscarOuFalhar($gatilhoId);
        $this->configuracoes->salvar($usuarioId, $gatilhoId, $dto->ativo, $dto->horario);

        return $this->comGatilho($this->configuracoes->doUsuarioNoGatilho($usuarioId, $gatilhoId), $usuarioId, $gatilho);
    }

    private function comGatilho(?ConfigNotificacao $config, int $usuarioId, GatilhoNotificacao $gatilho): ConfigNotificacao
    {
        $config ??= new ConfigNotificacao([
            'fk_id_usuario' => $usuarioId,
            'fk_id_gatilho' => $gatilho->id,
            'ativo' => true,
            'horario' => null,
        ]);

        return $config->setRelation('gatilho', $gatilho);
    }
}
