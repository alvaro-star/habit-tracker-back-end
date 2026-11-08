<?php

namespace App\Repositories;

use App\Models\ConfigNotificacao;

/** @extends BaseRepository<ConfigNotificacao> */
class ConfigNotificacaoRepository extends BaseRepository
{
    protected string $model = ConfigNotificacao::class;

    public function doUsuarioNoGatilho(int $usuarioId, int $gatilhoId): ?ConfigNotificacao
    {
        return $this->consultaDoUsuario($usuarioId)->where('fk_id_gatilho', $gatilhoId)->first();
    }

    /** Cria ou substitui a configuração; a chave composta impede o uso de save(). */
    public function salvar(int $usuarioId, int $gatilhoId, bool $ativo, ?string $horario): void
    {
        $this->consulta()->upsert(
            [['fk_id_usuario' => $usuarioId, 'fk_id_gatilho' => $gatilhoId, 'ativo' => $ativo, 'horario' => $horario]],
            ['fk_id_usuario', 'fk_id_gatilho'],
            ['ativo', 'horario'],
        );
    }
}
