<?php

namespace App\Services;

use App\DTOs\DTO;
use App\Enums\DiaSemana;
use App\Enums\Dificuldade;
use App\Enums\Frequencia;
use App\Enums\Polaridade;
use App\Enums\TipoMeta;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Habito;
use App\Repositories\HabitoRepository;
use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property HabitoRepository $repositorio
 *
 * @extends BaseService<Habito>
 */
class HabitoService extends BaseService
{
    public function __construct(
        HabitoRepository $repositorio,
        protected XpDiarioService $xpDiario,
    ) {
        parent::__construct($repositorio);
    }

    /** @param array<string, mixed>|DTO $dados */
    public function criar(array|DTO $dados): Model
    {
        return DB::transaction(fn () => $this->comXpDeHojeAtualizado(
            $this->repositorio->criar($this->comRegrasDaMeta($this->comFrequenciaValidada($this->paraArray($dados)))),
        ));
    }

    /** @param array<string, mixed>|DTO $dados */
    public function criarParaUsuario(int $usuarioId, array|DTO $dados): Model
    {
        return DB::transaction(fn () => $this->comXpDeHojeAtualizado($this->repositorio->criar([
            ...$this->comRegrasDaMeta($this->comFrequenciaValidada($this->paraArray($dados))),
            'fk_id_usuario' => $usuarioId,
        ])));
    }

    /** @param array<string, mixed>|DTO $dados */
    public function atualizar(int|string $id, array|DTO $dados): Model
    {
        /** @var Habito $habito */
        $habito = $this->buscar($id);
        $dados = $this->comDataDeInativacao($this->paraArray($dados), $habito);
        $this->garantirTipoMetaEditavel($dados, $habito);

        return $this->repositorio->atualizar($habito, $this->comRegrasDaMeta($this->comFrequenciaValidada($dados, $habito), $habito));
    }

    /** @param array<string, mixed>|DTO $dados */
    public function atualizarDoUsuario(int $usuarioId, int|string $id, array|DTO $dados): Model
    {
        /** @var Habito $habito */
        $habito = $this->buscarDoUsuario($usuarioId, $id);
        $dados = $this->comDataDeInativacao($this->paraArray($dados), $habito);
        $this->garantirTipoMetaEditavel($dados, $habito);

        return $this->repositorio->atualizar($habito, $this->comRegrasDaMeta($this->comFrequenciaValidada($dados, $habito), $habito));
    }

    /**
     * Excluir um hábito só o desativa (ativo = false): o registro e os seus
     * check-ins continuam no banco, para o histórico e as estatísticas.
     */
    public function excluir(int|string $id): bool
    {
        /** @var Habito $habito */
        $habito = $this->buscar($id);

        return $this->desativar($habito);
    }

    public function excluirDoUsuario(int $usuarioId, int|string $id): bool
    {
        /** @var Habito $habito */
        $habito = $this->buscarDoUsuario($usuarioId, $id);

        return $this->desativar($habito);
    }

    /**
     * Confere se o dia_frequencia combina com a frequência do hábito:
     * diário sem dia, semanal com SEG a DOM e mensal com um dia de 1 a 31.
     *
     * @throws RegraDeNegocioException respondida como 422
     */
    public function validarFrequencia(Frequencia $frequencia, int|string|DiaSemana|null $dia): void
    {
        if (! $frequencia->aceitaDia($dia)) {
            throw new RegraDeNegocioException('dia_frequencia', $frequencia->mensagemDiaInvalido());
        }
    }

    /**
     * Hábitos ativos do usuário que devem ser cumpridos na data: todos os
     * diários, os semanais daquele dia da semana e os mensais daquele dia do
     * mês. Um hábito mensal num dia que o mês não tem (ex.: 31 em abril) não
     * aparece naquele mês. Hábitos criados depois da data ficam de fora.
     *
     * @return Collection<int, Habito>
     */
    public function habitosDiarios(int $usuarioId, CarbonInterface $data): Collection
    {
        return $this->repositorio->ativosDoUsuarioNoDia($usuarioId, $data, DiaSemana::daData($data));
    }

    /**
     * O tipo de meta só pode mudar enquanto o hábito não tem check-ins: os
     * registros já feitos (valor, percentual) dependem do tipo de meta. Enviar
     * o mesmo tipo de meta que já está salvo não é uma mudança.
     *
     * @param  array<string, mixed>  $dados
     *
     * @throws RegraDeNegocioException respondida como 422
     */
    private function garantirTipoMetaEditavel(array $dados, Habito $habito): void
    {
        $novo = $dados['tipo_meta'] ?? null;
        $novo = $novo instanceof BackedEnum ? $novo->value : $novo;

        if ($novo === null || $novo === $habito->tipo_meta) {
            return;
        }

        if ($this->repositorio->temRegistros($habito)) {
            throw new RegraDeNegocioException('tipo_meta', 'O tipo de meta não pode ser alterado: o hábito já tem check-ins registrados.');
        }
    }

    /**
     * Só é possível excluir (desativar) um hábito no próprio dia em que ele foi
     * criado; um hábito criado num dia anterior a hoje devolve 422. A data da
     * desativação fica em inactivated_at.
     *
     * @throws RegraDeNegocioException
     */
    private function desativar(Habito $habito): bool
    {
        if ($habito->created_at->toDateString() < CarbonImmutable::today()->toDateString()) {
            throw new RegraDeNegocioException(
                'id',
                'O hábito não pode ser excluído: só é possível excluir um hábito no dia em que ele foi criado ('.$habito->created_at->toDateString().').',
            );
        }

        DB::transaction(fn () => $this->comXpDeHojeAtualizado($this->repositorio->atualizar($habito, [
            'ativo' => false,
            'inactivated_at' => $habito->inactivated_at ?? now(),
        ])));

        return true;
    }

    /**
     * Mantém o inactivated_at coerente quando o PATCH muda o campo ativo: ao
     * desativar, guarda o momento; ao reativar, limpa.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function comDataDeInativacao(array $dados, Habito $habito): array
    {
        if (! array_key_exists('ativo', $dados)) {
            return $dados;
        }

        if ($dados['ativo']) {
            $dados['inactivated_at'] = null;
        } elseif ($habito->ativo) {
            $dados['inactivated_at'] = now();
        }

        return $dados;
    }

    /**
     * Um hábito novo entra no ∑W total de hoje (o XP de hoje cai) e um hábito
     * desativado sai dele (o XP de hoje sobe): o XP de hoje é recalculado e a
     * diferença é aplicada ao dragão e ao usuário.
     */
    private function comXpDeHojeAtualizado(Habito $habito): Habito
    {
        $this->xpDiario->atualizarXpDeHoje($habito->fk_id_usuario);

        return $habito;
    }

    /**
     * Completa os dados com a frequência e o dia já salvos (num PATCH parcial)
     * e valida a combinação. Ao virar diário, o dia antigo é apagado.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function comFrequenciaValidada(array $dados, ?Habito $atual = null): array
    {
        $frequencia = Frequencia::from($dados['frequencia'] ?? $atual->frequencia ?? Frequencia::DIARIA->value);

        if ($frequencia === Frequencia::DIARIA && ! array_key_exists('dia_frequencia', $dados)) {
            $dados['dia_frequencia'] = null;
        }

        $dia = array_key_exists('dia_frequencia', $dados) ? $dados['dia_frequencia'] : $atual?->dia_frequencia;
        $this->validarFrequencia($frequencia, $dia);

        if ($dia !== null) {
            $dados['dia_frequencia'] = $dia instanceof DiaSemana ? $dia->value : (string) $dia;
        }

        return $dados;
    }

    /**
     * Regras da meta que dependem do tipo de meta e da polaridade: a unidade
     * (MOD3-TASK8), a meta positiva (MOD3-TASK3) e o início da abstinência dos
     * hábitos ruins (MOD3-TASK4). Num PATCH, completa com o que já está salvo.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     *
     * @throws RegraDeNegocioException respondida como 422
     */
    private function comRegrasDaMeta(array $dados, ?Habito $atual = null): array
    {
        $dados = $this->comUnidadeDoTipoDeMeta($dados, $atual);

        $polaridade = $this->enumOuNulo(Polaridade::class, $dados['polaridade'] ?? $atual->polaridade ?? Polaridade::BOM);
        $tipo = $this->enumOuNulo(TipoMeta::class, $dados['tipo_meta'] ?? $atual->tipo_meta ?? null);

        return $this->comInicioDaAbstinencia($this->comMetaPositiva($dados, $polaridade, $tipo), $polaridade, $atual);
    }

    /**
     * Meta positiva (MOD3-TASK3): o valor_meta é o quanto o usuário quer fazer
     * e só existe nos hábitos BOM de TEMPO ou QUANTIDADE, sempre maior que zero
     * (uma meta zero vira "sem meta").
     * Na REALIZACAO não há meta numérica (o valor é descartado). Um hábito RUIM
     * não tem meta positiva: enviar valor_meta devolve 422, e um hábito que
     * vira RUIM perde a meta que tinha.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     *
     * @throws RegraDeNegocioException respondida como 422
     */
    private function comMetaPositiva(array $dados, Polaridade $polaridade, ?TipoMeta $tipo): array
    {
        $meta = $dados['valor_meta'] ?? null;

        if ($polaridade === Polaridade::RUIM) {
            if ($meta !== null) {
                throw new RegraDeNegocioException('valor_meta', 'Hábitos ruins não têm meta positiva: o progresso deles é o tempo sem praticar.');
            }

            $dados['valor_meta'] = null;

            return $dados;
        }

        // Meta zero é o mesmo que nenhuma meta. A request recusa zero (gt:0); aqui ele só
        // chega por chamadas internas, como o onboarding com a meta_padrao 0 de um modelo.
        if ($tipo === TipoMeta::REALIZACAO || ($meta !== null && (float) $meta <= 0)) {
            $dados['valor_meta'] = null;
        }

        return $dados;
    }

    /**
     * Início da abstinência (MOD3-TASK4): só os hábitos RUIM o têm. Ao cadastrar
     * um hábito ruim (ou transformar um hábito bom em ruim) sem enviar a data,
     * a contagem começa agora; uma data futura devolve 422. Nos hábitos BOM o
     * campo fica nulo.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     *
     * @throws RegraDeNegocioException respondida como 422
     */
    private function comInicioDaAbstinencia(array $dados, Polaridade $polaridade, ?Habito $atual): array
    {
        if ($polaridade === Polaridade::BOM) {
            $dados['inicio_abstinencia'] = null;

            return $dados;
        }

        $inicio = $dados['inicio_abstinencia'] ?? null;

        if ($inicio !== null) {
            if (CarbonImmutable::parse($inicio)->isFuture()) {
                throw new RegraDeNegocioException('inicio_abstinencia', 'O início da abstinência não pode ser no futuro.');
            }
        } elseif ($atual === null || ! $atual->ehRuim()) {
            $dados['inicio_abstinencia'] = now();
        }

        return $dados;
    }

    /**
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T|null
     */
    private function enumOuNulo(string $enum, mixed $valor): ?BackedEnum
    {
        if ($valor === null || $valor instanceof $enum) {
            return $valor;
        }

        return $enum::from($valor instanceof BackedEnum ? $valor->value : $valor);
    }

    /**
     * A unidade depende do tipo de meta (MOD3-TASK8): só a QUANTIDADE aceita
     * uma unidade definida pelo usuário (copos, páginas, R$...). No TEMPO ela é
     * sempre minutos e na REALIZACAO não há unidade: uma unidade enviada para
     * esses tipos é descartada, como a dificuldade. Ao trocar um hábito para
     * QUANTIDADE sem enviar a unidade, a unidade antiga (minutos) é apagada.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function comUnidadeDoTipoDeMeta(array $dados, ?Habito $atual = null): array
    {
        $tipo = $dados['tipo_meta'] ?? $atual->tipo_meta ?? null;

        if ($tipo === null) {
            return $dados;
        }

        $tipo = $tipo instanceof TipoMeta ? $tipo : TipoMeta::from($tipo);

        if (! $tipo->aceitaUnidadeCustomizada()) {
            $dados['unidade'] = $tipo->unidadeFixa();

            return $dados;
        }

        if (array_key_exists('unidade', $dados)) {
            $unidade = trim((string) $dados['unidade']);
            $dados['unidade'] = $unidade === '' ? null : $unidade;
        } elseif ($atual !== null && $atual->tipo_meta !== $tipo->value) {
            $dados['unidade'] = null;
        }

        return $dados;
    }

    /**
     * A dificuldade é sempre gerada pelo peso (Dificuldade::doPeso): uma
     * dificuldade informada é descartada, e trocar o peso recalcula a
     * dificuldade. Vale para todas as operações de criação e edição.
     *
     * @throws RegraDeNegocioException se o peso estiver fora de 1 a 20
     */
    protected function paraArray(array|DTO $dados): array
    {
        $dados = parent::paraArray($dados);
        unset($dados['dificuldade']);

        if (isset($dados['peso'])) {
            $peso = (int) $dados['peso'];

            if ($peso < Dificuldade::PESO_MINIMO || $peso > Dificuldade::PESO_MAXIMO) {
                throw new RegraDeNegocioException('peso', 'O peso deve ficar entre '.Dificuldade::PESO_MINIMO.' e '.Dificuldade::PESO_MAXIMO.'.');
            }

            $dados['peso'] = $peso;
            $dados['dificuldade'] = Dificuldade::doPeso($peso)->value;
        }

        return $dados;
    }
}
