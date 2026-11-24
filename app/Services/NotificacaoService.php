<?php

namespace App\Services;

use App\DTOs\Notifications\NotificacaoManualDTO;
use App\Enums\TipoGatilho;
use App\Exceptions\RegraDeNegocioException;
use App\Jobs\EnviarAvisoDeInatividade;
use App\Jobs\EnviarLembreteDeAcesso;
use App\Jobs\EnviarLembreteDeHabito;
use App\Jobs\EnviarNotificacaoManual;
use App\Models\GatilhoNotificacao;
use App\Models\Habito;
use App\Models\User;
use App\Notifications\GatilhoNotification;
use App\Notifications\NotificacaoManual;
use App\Repositories\ConfigNotificacaoRepository;
use App\Repositories\GatilhoNotificacaoRepository;
use App\Repositories\HabitoRepository;
use App\Repositories\UsuarioRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Kreait\Firebase\Exception\FirebaseException;

class NotificacaoService
{
    private const LIMITE_DE_RECEBIDAS = 50;

    /** Dias sem acesso que geram cada aviso. */
    private const AVISOS_DE_INATIVIDADE = [
        3 => TipoGatilho::INATIVIDADE_3_DIAS,
        7 => TipoGatilho::INATIVIDADE_7_DIAS,
    ];

    /** Menor que o intervalo de 4 horas do cron, para tolerar atrasos. */
    private const HORAS_SEM_REPETIR_STREAK = 3;

    private const MINUTOS_DO_BLOCO_DE_LEMBRETES = 30;

    public function __construct(
        protected UsuarioRepository $usuarios,
        protected HabitoRepository $habitos,
        protected GatilhoNotificacaoRepository $gatilhos,
        protected ConfigNotificacaoRepository $configuracoes,
        protected StreakService $streaks,
        protected XpDiarioService $xp,
    ) {}

    /** @return Collection<int, DatabaseNotification> as mais recentes primeiro */
    public function recebidas(User $usuario): Collection
    {
        return $usuario->notifications()->limit(self::LIMITE_DE_RECEBIDAS)->get()->toBase();
    }

    /**
     * A cada revisão, avisa quem tem sequência ativa e ainda não fez todo o XP do
     * dia, a partir do horário configurado (ou do padrão). Quem não tem hábitos
     * no dia não recebe. Também corrige a sequência de quem já a perdeu, porque
     * nenhum check-in faria isso.
     *
     * @return int quantidade de avisos enviados
     */
    public function enviarAvisosDeStreak(): int
    {
        $gatilho = $this->gatilhos->doTipo(TipoGatilho::STREAK_EM_RISCO);
        $enviados = 0;

        $this->usuarios->comStreakAtiva(function (User $usuario) use ($gatilho, &$enviados) {
            if ($this->streaks->atualizar($usuario->id) === 0 || $gatilho === null
                || ! $this->xp->faltaXpNoDia($usuario->id, today())) {
                return;
            }

            $config = $this->configuracoes->doUsuarioNoGatilho($usuario->id, $gatilho->id);
            $horario = $config->horario ?? config('notifications.horario_streak_em_risco');

            if (now()->lt(today()->setTimeFromTimeString($horario))) {
                return;
            }

            $enviados += (int) $this->enviar($usuario, $gatilho, now()->subHours(self::HORAS_SEM_REPETIR_STREAK));
        });

        return $enviados;
    }

    /**
     * Agenda, para o momento exato, os avisos de quem completa 3 ou 7 dias sem
     * acesso hoje. Quem nunca acessou conta a partir do cadastro.
     *
     * @return int quantidade de avisos agendados
     */
    public function agendarAvisosDeInatividade(): int
    {
        $agendados = 0;

        foreach (self::AVISOS_DE_INATIVIDADE as $dias => $tipo) {
            if ($this->gatilhos->doTipo($tipo) === null) {
                continue;
            }

            $inicio = today()->subDays($dias);
            $fim = $inicio->copy()->endOfDay();

            $this->usuarios->comUltimoAcessoEntre($inicio, $fim, function (User $usuario) use ($dias, $tipo, &$agendados) {
                $ultimoAcesso = $usuario->ultimo_acesso ?? $usuario->created_at;

                EnviarAvisoDeInatividade::dispatch($usuario->id, $tipo, $ultimoAcesso->format('Y-m-d H:i:s'))
                    ->delay($ultimoAcesso->copy()->addDays($dias));
                $agendados++;
            });
        }

        return $agendados;
    }

    /**
     * Executado pelo job quando o prazo se completa. Não envia se o usuário
     * voltou a acessar depois do agendamento.
     *
     * @return bool se a notificação foi enviada
     */
    public function enviarAvisoDeInatividade(int $usuarioId, TipoGatilho $tipo, string $ultimoAcesso): bool
    {
        $usuario = $this->usuarios->encontrar($usuarioId);
        $gatilho = $this->gatilhos->doTipo($tipo);

        if ($usuario === null || $gatilho === null) {
            return false;
        }

        $acessoAtual = $usuario->ultimo_acesso ?? $usuario->created_at;

        if ($acessoAtual->format('Y-m-d H:i:s') !== $ultimoAcesso) {
            return false;
        }

        return $this->enviar($usuario, $gatilho, $acessoAtual);
    }

    /**
     * A cada revisão, despacha o lembrete de entrar no app para quem ainda não
     * acessou hoje, a partir do horário configurado (ou do padrão). Cada usuário
     * recebe no máximo um por dia.
     *
     * @return int quantidade de lembretes despachados
     */
    public function agendarLembretesDeAcesso(): int
    {
        $gatilho = $this->gatilhos->doTipo(TipoGatilho::LEMBRETE_ACESSO);

        if ($gatilho === null) {
            return 0;
        }

        $hoje = today();
        $agendados = 0;

        $this->usuarios->comTodosSemAcessoDesde($hoje, function (User $usuario) use ($gatilho, $hoje, &$agendados) {
            $config = $this->configuracoes->doUsuarioNoGatilho($usuario->id, $gatilho->id);
            $horario = $config->horario ?? config('notifications.horario_lembrete_acesso');

            if ($config?->ativo === false
                || now()->lt($hoje->copy()->setTimeFromTimeString($horario))
                || $usuario->notifications()->where('type', $gatilho->tipo)->where('created_at', '>=', $hoje)->exists()) {
                return;
            }

            EnviarLembreteDeAcesso::dispatch($usuario->id, $hoje->toDateString());
            $agendados++;
        });

        return $agendados;
    }

    /**
     * Executado pelo job. Não envia se o dia já passou ou se o usuário entrou no
     * app depois do despacho, e o enviar() impede um segundo lembrete no dia.
     *
     * @return bool se a notificação foi enviada
     */
    public function enviarLembreteDeAcesso(int $usuarioId, string $data): bool
    {
        $dia = CarbonImmutable::parse($data)->startOfDay();
        $gatilho = $this->gatilhos->doTipo(TipoGatilho::LEMBRETE_ACESSO);

        if ($gatilho === null || ! $dia->isSameDay(today())) {
            return false;
        }

        $usuario = $this->usuarios->encontrarSemAcessoDesde($usuarioId, $dia);

        return $usuario !== null && $this->enviar($usuario, $gatilho, $dia);
    }

    /**
     * Agenda um job, no horário exato, para cada lembrete do bloco atual de 30
     * minutos: o cron das 07:00 cobre de 07:00 a antes das 07:30, e o das 07:30,
     * de 07:30 a antes das 08:00.
     *
     * @return int quantidade de lembretes agendados
     */
    public function agendarLembretesDeHabitos(): int
    {
        if ($this->gatilhos->doTipo(TipoGatilho::LEMBRETE_DIARIO) === null) {
            return 0;
        }

        $agora = now();
        $inicio = $agora->copy()->setTime($agora->hour, $agora->minute < self::MINUTOS_DO_BLOCO_DE_LEMBRETES ? 0 : self::MINUTOS_DO_BLOCO_DE_LEMBRETES);
        $fim = $inicio->copy()->addMinutes(self::MINUTOS_DO_BLOCO_DE_LEMBRETES - 1);
        $agendados = 0;

        $this->habitos->comLembretePendente($inicio, $inicio->format('H:i'), $fim->format('H:i').':59', function (Habito $habito) use ($inicio, &$agendados) {
            $horario = substr($habito->horario_lembrete, 0, 5);

            EnviarLembreteDeHabito::dispatch($habito->id, $habito->fk_id_usuario, $inicio->toDateString(), $horario)
                ->delay($inicio->copy()->setTimeFromTimeString($horario));
            $agendados++;
        });

        return $agendados;
    }

    /**
     * Executado pelo job no horário do lembrete. Confere de novo o hábito, que
     * pode ter mudado depois do agendamento, e envia uma vez por dia.
     *
     * @return bool se a notificação foi enviada
     */
    public function enviarLembreteDeHabito(int $habitoId, int $usuarioId, string $data, string $horario): bool
    {
        $gatilho = $this->gatilhos->doTipo(TipoGatilho::LEMBRETE_DIARIO);
        $dia = CarbonImmutable::parse($data);
        $habito = $gatilho !== null ? $this->habitos->lembretePendente($habitoId, $usuarioId, $dia, $horario) : null;

        if ($habito === null) {
            return false;
        }

        $config = $this->configuracoes->doUsuarioNoGatilho($usuarioId, $gatilho->id);

        if ($config?->ativo === false || ! $this->habitos->marcarLembreteEnviado($habito->id, $dia)) {
            return false;
        }

        $this->entregar($habito->usuario, new GatilhoNotification($gatilho, $habito));

        return true;
    }

    /**
     * Agenda a notificação para o dia e o horário escolhidos: um job para o
     * usuário informado ou, sem usuário, um job para cada usuário cadastrado. O
     * minuto atual é aceito e sai logo em seguida.
     *
     * @return array{enviar_em: CarbonImmutable, destinatarios: int}
     *
     * @throws RegraDeNegocioException respondida como 422
     */
    public function agendarNotificacaoManual(int $remetenteId, NotificacaoManualDTO $dados): array
    {
        $enviarEm = CarbonImmutable::parse("{$dados->data} {$dados->horario}");

        if ($enviarEm->lt(now()->startOfMinute())) {
            throw new RegraDeNegocioException('horario', 'O dia e o horário do envio não podem estar no passado.');
        }

        if ($dados->fk_id_usuario !== null) {
            $this->agendarEnvioManual($dados->fk_id_usuario, $remetenteId, $dados, $enviarEm);

            return ['enviar_em' => $enviarEm, 'destinatarios' => 1];
        }

        $destinatarios = 0;

        $this->usuarios->comTodos(function (User $usuario) use ($remetenteId, $dados, $enviarEm, &$destinatarios) {
            $this->agendarEnvioManual($usuario->id, $remetenteId, $dados, $enviarEm);
            $destinatarios++;
        });

        return ['enviar_em' => $enviarEm, 'destinatarios' => $destinatarios];
    }

    private function agendarEnvioManual(int $destinatarioId, int $remetenteId, NotificacaoManualDTO $dados, CarbonImmutable $enviarEm): void
    {
        EnviarNotificacaoManual::dispatch($destinatarioId, $remetenteId, $dados->titulo, $dados->texto)
            ->delay($enviarEm);
    }

    /**
     * Executado pelo job no horário escolhido. Não envia se o destinatário
     * excluiu a conta depois do agendamento.
     *
     * @return bool se a notificação foi enviada
     */
    public function enviarNotificacaoManual(int $destinatarioId, int $remetenteId, string $titulo, string $texto): bool
    {
        $destinatario = $this->usuarios->encontrar($destinatarioId);

        if ($destinatario === null) {
            return false;
        }

        $this->entregar($destinatario, new NotificacaoManual($remetenteId, $titulo, $texto));

        return true;
    }

    /** Envia se o gatilho não foi desativado pelo usuário nem já enviado desde a data. */
    private function enviar(User $usuario, GatilhoNotificacao $gatilho, CarbonInterface $desde): bool
    {
        $config = $this->configuracoes->doUsuarioNoGatilho($usuario->id, $gatilho->id);

        if ($config?->ativo === false
            || $usuario->notifications()->where('type', $gatilho->tipo)->where('created_at', '>=', $desde)->exists()) {
            return false;
        }

        $this->entregar($usuario, new GatilhoNotification($gatilho));

        return true;
    }

    /**
     * Uma falha do Firebase não pode impedir o envio aos próximos usuários.
     * O banco é o primeiro canal, então a notificação já ficou gravada.
     */
    private function entregar(User $usuario, Notification $notificacao): void
    {
        try {
            $usuario->notify($notificacao);
        } catch (FirebaseException $erro) {
            report($erro);
        }
    }
}
