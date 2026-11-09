<?php

namespace App\Services;

use App\Enums\TipoGatilho;
use App\Models\GatilhoNotificacao;
use App\Models\User;
use App\Notifications\GatilhoNotification;
use App\Repositories\ConfigNotificacaoRepository;
use App\Repositories\GatilhoNotificacaoRepository;
use App\Repositories\UsuarioRepository;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificacaoService
{
    private const LIMITE_DE_RECEBIDAS = 50;

    private const DIAS_INATIVIDADE_CURTA = 3;

    private const DIAS_INATIVIDADE_LONGA = 7;

    public function __construct(
        protected UsuarioRepository $usuarios,
        protected GatilhoNotificacaoRepository $gatilhos,
        protected ConfigNotificacaoRepository $configuracoes,
        protected StreakService $streaks,
    ) {}

    /** @return Collection<int, DatabaseNotification> as mais recentes primeiro */
    public function recebidas(User $usuario): Collection
    {
        return $usuario->notifications()->limit(self::LIMITE_DE_RECEBIDAS)->get()->toBase();
    }

    /**
     * Avisa quem tem sequência ativa e ainda não concluiu um hábito hoje, uma vez
     * por dia, a partir do horário configurado (ou do padrão). Também corrige a
     * sequência de quem já a perdeu, porque nenhum check-in faria isso.
     *
     * @return int quantidade de avisos enviados
     */
    public function enviarAvisosDeStreak(): int
    {
        $gatilho = $this->gatilhos->doTipo(TipoGatilho::STREAK_EM_RISCO);
        $enviados = 0;

        $this->usuarios->comStreakAtiva(function (User $usuario) use ($gatilho, &$enviados) {
            if ($this->streaks->atualizar($usuario->id) === 0 || $gatilho === null
                || $this->streaks->concluiuHoje($usuario->id)) {
                return;
            }

            $config = $this->configuracoes->doUsuarioNoGatilho($usuario->id, $gatilho->id);
            $horario = $config->horario ?? config('notifications.horario_streak_em_risco');

            if (now()->lt(today()->setTimeFromTimeString($horario))) {
                return;
            }

            $enviados += (int) $this->enviar($usuario, $gatilho, today());
        });

        return $enviados;
    }

    /**
     * Envia o gatilho de 3 ou de 7 dias sem acesso, uma vez por período de
     * inatividade. Quem já passou dos 7 dias recebe só o aviso de 7.
     *
     * @return int quantidade de notificações enviadas
     */
    public function enviarReengajamento(): int
    {
        $curta = $this->gatilhos->doTipo(TipoGatilho::INATIVIDADE_3_DIAS);
        $longa = $this->gatilhos->doTipo(TipoGatilho::INATIVIDADE_7_DIAS);
        $limiteLonga = now()->subDays(self::DIAS_INATIVIDADE_LONGA);
        $enviados = 0;

        $this->usuarios->inativosDesde(now()->subDays(self::DIAS_INATIVIDADE_CURTA), function (User $usuario) use ($curta, $longa, $limiteLonga, &$enviados) {
            $ultimoAcesso = $usuario->ultimo_acesso ?? $usuario->created_at;
            $gatilho = $ultimoAcesso->lte($limiteLonga) ? $longa : $curta;

            if ($gatilho !== null) {
                $enviados += (int) $this->enviar($usuario, $gatilho, $ultimoAcesso);
            }
        });

        return $enviados;
    }

    /** Envia se o gatilho não foi desativado pelo usuário nem já enviado desde a data. */
    private function enviar(User $usuario, GatilhoNotificacao $gatilho, CarbonInterface $desde): bool
    {
        $config = $this->configuracoes->doUsuarioNoGatilho($usuario->id, $gatilho->id);

        if ($config?->ativo === false
            || $usuario->notifications()->where('type', $gatilho->tipo)->where('created_at', '>=', $desde)->exists()) {
            return false;
        }

        $usuario->notify(new GatilhoNotification($gatilho));

        return true;
    }
}
