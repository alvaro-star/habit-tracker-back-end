<?php

namespace Database\Seeders;

use App\DTOs\CheckIns\RegistroHabitoDTO;
use App\Enums\ElementoDragao;
use App\Enums\Polaridade;
use App\Enums\StatusDragao;
use App\Enums\StatusRegistro;
use App\Enums\TipoMeta;
use App\Models\Categoria;
use App\Models\ConfigNotificacao;
use App\Models\GatilhoNotificacao;
use App\Models\Habito;
use App\Models\Insignia;
use App\Models\User;
use App\Models\UsuarioInsignia;
use App\Services\DragaoService;
use App\Services\HabitoService;
use App\Services\RegistroHabitoService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Dados do servidor de testes: o usuário admin@gmail.com (senha `senha123`)
 * com um dragão no Santuário, um dragão ativo, hábitos e 30 dias de check-ins.
 *
 * Os registros passam pelos services, então o XP diário, o xp_ciclo, o estágio
 * do dragão, o XP vitalício e o streak saem das mesmas regras da API. Os
 * check-ins usam uma semente fixa: toda execução gera os mesmos dados.
 */
class DadosDeTesteSeeder extends Seeder
{
    public const EMAIL = 'admin@gmail.com';

    public const SENHA = 'senha123';

    /** Dias de histórico até hoje (inclusive). */
    private const DIAS = 30;

    public function __construct(
        private readonly DragaoService $dragoes,
        private readonly HabitoService $habitos,
        private readonly RegistroHabitoService $checkIns,
    ) {}

    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => self::EMAIL],
            ['name' => 'Admin', 'password' => self::SENHA],
        );
        $admin->forceFill(['email_verified_at' => now(), 'onboarding_concluido' => true])->save();

        // Os dados de exemplo só são criados uma vez; a senha acima é sempre redefinida.
        if ($admin->habitos()->exists()) {
            return;
        }

        $hoje = CarbonImmutable::today();
        $inicio = $hoje->subDays(self::DIAS - 1);
        $categorias = Categoria::query()->pluck('id', 'nome');

        $admin->objetivos()->syncWithoutDetaching([$categorias['Saúde'], $categorias['Estudos']]);

        $this->criarDragoes($admin->id, $inicio, $hoje);
        $habitos = $this->criarHabitos($admin->id, $categorias->all(), $inicio);
        $this->registrarCheckIns($admin, $habitos, $inicio, $hoje);
        $this->criarInsigniasENotificacoes($admin->id, $inicio);
    }

    /** Um dragão que já concluiu o ciclo (Santuário) e o dragão ativo, nascido no início do histórico. */
    private function criarDragoes(int $usuarioId, CarbonImmutable $inicio, CarbonImmutable $hoje): void
    {
        $this->dragoes->criarParaUsuario($usuarioId, [
            'nome' => 'Ignis',
            'ovo' => 'ovo_vermelho',
            'elemento' => ElementoDragao::FOGO->value,
            'xp_ciclo' => 9000,
            'status' => StatusDragao::SANTUARIO->value,
            'data_nascimento' => $hoje->subDays(400)->toDateString(),
            'data_termino' => $inicio->subDay()->toDateString(),
        ]);

        $this->dragoes->criarParaUsuario($usuarioId, [
            'nome' => 'Faísca',
            'ovo' => 'ovo_azul',
            'xp_ciclo' => 0,
            'status' => StatusDragao::ATIVO->value,
            'data_nascimento' => $inicio->toDateString(),
        ]);
    }

    /**
     * Hábitos de todos os tipos de meta e frequências, criados no início do
     * histórico (o created_at vem do banco, então é ajustado depois).
     *
     * @param  array<string, int>  $categorias
     * @return list<Habito>
     */
    private function criarHabitos(int $usuarioId, array $categorias, CarbonImmutable $inicio): array
    {
        $dados = [
            ['Beber água', 'Saúde', Polaridade::BOM, TipoMeta::QUANTIDADE, 'DIARIA', null, 2, 'litros', 5],
            ['Caminhar', 'Saúde', Polaridade::BOM, TipoMeta::TEMPO, 'DIARIA', null, 30, null, 10],
            ['Estudar para as provas', 'Estudos', Polaridade::BOM, TipoMeta::TEMPO, 'DIARIA', null, 60, null, 15],
            ['Planejar o dia', 'Produtividade', Polaridade::BOM, TipoMeta::REALIZACAO, 'DIARIA', null, null, null, 4],
            ['Ler um livro', 'Desenvolvimento Pessoal', Polaridade::BOM, TipoMeta::QUANTIDADE, 'SEMANAL', 'SAB', 20, 'páginas', 8],
            ['Conferir o orçamento', 'Finanças', Polaridade::BOM, TipoMeta::REALIZACAO, 'MENSAL', '5', null, null, 6],
            ['Roer as unhas', 'Saúde', Polaridade::RUIM, TipoMeta::REALIZACAO, 'DIARIA', null, null, null, 12],
        ];

        $habitos = [];

        foreach ($dados as [$nome, $categoria, $polaridade, $tipoMeta, $frequencia, $dia, $meta, $unidade, $peso]) {
            /** @var Habito $habito */
            $habito = $this->habitos->criarParaUsuario($usuarioId, [
                'fk_id_categoria' => $categorias[$categoria],
                'nome' => $nome,
                'polaridade' => $polaridade->value,
                'tipo_meta' => $tipoMeta->value,
                'frequencia' => $frequencia,
                'dia_frequencia' => $dia,
                'valor_meta' => $meta,
                'unidade' => $unidade,
                'peso' => $peso,
            ]);

            Habito::query()->whereKey($habito->id)->update(['created_at' => $inicio->startOfDay()]);
            $habitos[] = $habito->refresh();
        }

        return $habitos;
    }

    /**
     * Check-ins de cada dia do histórico, só nos dias em que o hábito cai. Os
     * hábitos bons são cumpridos ~75% das vezes (às vezes pela metade); o hábito
     * ruim é marcado só em 2 dias (as recaídas do contador de abstinência).
     *
     * @param  list<Habito>  $habitos
     */
    private function registrarCheckIns(User $admin, array $habitos, CarbonImmutable $inicio, CarbonImmutable $hoje): void
    {
        mt_srand(2026);
        $admin->refresh();
        $recaidas = [$inicio->addDays(4)->toDateString(), $inicio->addDays(17)->toDateString()];

        for ($data = $inicio; $data->lessThanOrEqualTo($hoje); $data = $data->addDay()) {
            $doDia = $this->habitos->habitosDiarios($admin->id, $data)->pluck('id')->all();

            foreach ($habitos as $habito) {
                if (! in_array($habito->id, $doDia, true)) {
                    continue;
                }

                $dados = $this->checkInDoDia($habito, $data, $recaidas);

                if ($dados !== null) {
                    $this->checkIns->registrar($admin, RegistroHabitoDTO::fromArray($dados + [
                        'fk_id_habito' => $habito->id,
                        'data' => $data->toDateString(),
                        'checked' => true,
                    ]));
                }
            }
        }
    }

    /**
     * @param  list<string>  $recaidas
     * @return array<string, mixed>|null null quando o hábito não é marcado no dia
     */
    private function checkInDoDia(Habito $habito, CarbonImmutable $data, array $recaidas): ?array
    {
        if ($habito->polaridade === Polaridade::RUIM->value) {
            return in_array($data->toDateString(), $recaidas, true)
                ? ['status' => StatusRegistro::CONCLUIDO->value]
                : null;
        }

        $sorteio = mt_rand(1, 100);

        if ($sorteio > 75) {
            return null;
        }

        $meta = (float) ($habito->valor_meta ?? 1);
        $parcial = $sorteio > 60;

        return match (TipoMeta::from($habito->tipo_meta)) {
            TipoMeta::REALIZACAO => ['status' => StatusRegistro::CONCLUIDO->value],
            TipoMeta::TEMPO => ['valor_realizado' => $parcial ? round($meta * 0.5) : $meta + mt_rand(0, 20)],
            TipoMeta::QUANTIDADE => ['valor_realizado' => $parcial ? round($meta * 0.6, 1) : $meta],
        };
    }

    private function criarInsigniasENotificacoes(int $usuarioId, CarbonImmutable $inicio): void
    {
        $insignias = [
            ['Primeiro passo', 'Fez o primeiro check-in.', 'https://example.com/insignias/primeiro-passo.png'],
            ['Uma semana firme', 'Manteve uma sequência de 7 dias.', 'https://example.com/insignias/semana.png'],
            ['Guardião do Santuário', 'Levou um dragão até o Santuário.', 'https://example.com/insignias/santuario.png'],
        ];

        foreach ($insignias as $indice => [$nome, $descricao, $icone]) {
            $insignia = Insignia::firstOrCreate(['nome' => $nome], ['descricao' => $descricao, 'icone_url' => $icone]);

            if ($indice !== 1) {
                UsuarioInsignia::firstOrCreate(
                    ['fk_id_usuario' => $usuarioId, 'fk_id_insignia' => $insignia->id],
                    ['data_conquista' => $inicio->addDays($indice)],
                );
            }
        }

        foreach (GatilhoNotificacao::query()->pluck('id') as $gatilhoId) {
            ConfigNotificacao::firstOrCreate(
                ['fk_id_usuario' => $usuarioId, 'fk_id_gatilho' => $gatilhoId],
                ['ativo' => true, 'horario' => '20:00'],
            );
        }
    }
}
