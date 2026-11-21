<?php

namespace Tests\Feature\Notifications;

use App\Enums\TipoGatilho;
use App\Models\ConfigNotificacao;
use App\Models\GatilhoNotificacao;
use App\Models\Habito;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Services\NotificacaoService;
use Carbon\Carbon;
use Database\Seeders\GatilhoNotificacaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use NotificationChannels\Fcm\FcmMessage;
use Tests\TestCase;

class LembreteDeHabitoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private int $categoria;

    protected function setUp(): void
    {
        parent::setUp();
        // Segunda-feira, 07:30 em Campo Grande.
        Carbon::setTestNow('2026-11-09 07:30:00');
        config(['notifications.push_ativo' => false]);
        $this->seed(GatilhoNotificacaoSeeder::class);
        $this->usuario = User::factory()->create();
        $this->categoria = DB::table('categoria')->insertGetId(['nome' => 'Saúde']);
    }

    /** @param array<string, mixed> $dados */
    private function habito(array $dados = []): Habito
    {
        return Habito::create([
            'fk_id_usuario' => $this->usuario->id, 'fk_id_categoria' => $this->categoria, 'nome' => 'Ler',
            'polaridade' => 'BOM', 'tipo_meta' => 'REALIZACAO', 'frequencia' => 'DIARIA',
            'dificuldade' => 'FACIL', 'peso' => 1, 'ativo' => true, 'horario_lembrete' => '07:30',
            ...$dados,
        ]);
    }

    private function enviar(): int
    {
        return app(NotificacaoService::class)->enviarLembretesDeHabitos();
    }

    public function test_envia_na_hora_local_com_o_nome_do_habito_uma_vez_por_dia(): void
    {
        $habito = $this->habito();

        $this->assertSame(1, $this->enviar());
        $this->assertSame(0, $this->enviar());

        $notificacao = $this->usuario->notifications()->sole();
        $this->assertSame(TipoGatilho::LEMBRETE_DIARIO->value, $notificacao->type);
        $this->assertSame('Hora de Ler! Seu dragão está contando com você.', $notificacao->data['texto']);
        $this->assertSame($habito->id, $notificacao->data['fk_id_habito']);

        Carbon::setTestNow('2026-11-10 07:30:00');
        $this->assertSame(1, $this->enviar());
    }

    public function test_horario_e_de_campo_grande_e_nao_utc(): void
    {
        $this->habito();
        Carbon::setTestNow(Carbon::parse('2026-11-09 07:30:00', 'UTC'));

        $this->assertSame(0, $this->enviar());
    }

    public function test_envia_o_que_venceu_desde_a_ultima_revisao(): void
    {
        $this->habito(['horario_lembrete' => '07:10']);
        $this->habito(['horario_lembrete' => '06:55']);
        $this->habito(['horario_lembrete' => '06:54']);
        $this->habito(['horario_lembrete' => '07:31']);

        $this->assertSame(2, $this->enviar());
    }

    public function test_virada_do_dia_envia_o_lembrete_do_fim_da_noite_uma_vez(): void
    {
        $habito = $this->habito(['horario_lembrete' => '23:45']);
        Carbon::setTestNow('2026-11-10 00:00:00');

        $this->assertSame(1, $this->enviar());
        $this->assertSame(0, $this->enviar());
        $this->assertSame('2026-11-09', substr((string) $habito->fresh()->ultimo_lembrete_em, 0, 10));
    }

    public function test_ignora_habito_ruim_inativo_ou_ja_concluido(): void
    {
        $this->habito(['polaridade' => 'RUIM']);
        $this->habito(['ativo' => false]);
        $concluido = $this->habito();
        RegistroHabito::create([
            'fk_id_habito' => $concluido->id, 'data' => '2026-11-09', 'status' => 'CONCLUIDO', 'percentual_cumprido' => 100,
        ]);

        $this->assertSame(0, $this->enviar());
    }

    public function test_respeita_a_frequencia(): void
    {
        $this->habito(['frequencia' => 'SEMANAL', 'dia_frequencia' => 'SEG']);
        $this->habito(['frequencia' => 'SEMANAL', 'dia_frequencia' => 'TER']);
        $this->habito(['frequencia' => 'MENSAL', 'dia_frequencia' => '9']);
        $this->habito(['frequencia' => 'MENSAL', 'dia_frequencia' => '10']);

        $this->assertSame(2, $this->enviar());
    }

    public function test_respeita_gatilho_desativado_pelo_usuario(): void
    {
        $this->habito();
        ConfigNotificacao::create([
            'fk_id_usuario' => $this->usuario->id,
            'fk_id_gatilho' => GatilhoNotificacao::where('tipo', TipoGatilho::LEMBRETE_DIARIO->value)->value('id'),
            'ativo' => false,
        ]);

        $this->assertSame(0, $this->enviar());
    }

    public function test_dispara_o_push_para_o_dispositivo(): void
    {
        config(['notifications.push_ativo' => true]);
        $this->usuario->forceFill(['token_dispositivo' => 'token-a'])->save();
        $habito = $this->habito();
        $mensageria = Mockery::mock(Messaging::class);
        $mensageria->shouldReceive('sendMulticast')->once()
            ->withArgs(fn (FcmMessage $mensagem, array $tokens) => $tokens === ['token-a']
                && $mensagem->notification->title === 'Ler'
                && $mensagem->notification->body === 'Hora de Ler! Seu dragão está contando com você.'
                && $mensagem->data['fk_id_habito'] === (string) $habito->id)
            ->andReturn(MulticastSendReport::withItems([
                SendReport::success(MessageTarget::with(MessageTarget::TOKEN, 'token-a'), ['name' => 'mensagem']),
            ]));
        $this->app->instance(Messaging::class, $mensageria);

        $this->assertSame(1, $this->enviar());
    }
}
