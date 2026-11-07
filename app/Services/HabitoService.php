<?php

namespace App\Services;

use App\DTOs\DTO;
use App\Enums\DiaSemana;
use App\Enums\Dificuldade;
use App\Enums\Frequencia;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Habito;
use App\Repositories\HabitoRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** @property HabitoRepository $repositorio */
class HabitoService extends BaseService
{
    public function __construct(HabitoRepository $repositorio)
    {
        parent::__construct($repositorio);
    }

    /** @param array<string, mixed>|DTO $dados */
    public function criar(array|DTO $dados): Model
    {
        return $this->repositorio->criar($this->comFrequenciaValidada($this->paraArray($dados)));
    }

    /** @param array<string, mixed>|DTO $dados */
    public function criarParaUsuario(int $usuarioId, array|DTO $dados): Model
    {
        return $this->repositorio->criar([
            ...$this->comFrequenciaValidada($this->paraArray($dados)),
            'fk_id_usuario' => $usuarioId,
        ]);
    }

    /** @param array<string, mixed>|DTO $dados */
    public function atualizar(int|string $id, array|DTO $dados): Model
    {
        /** @var Habito $habito */
        $habito = $this->buscar($id);

        return $this->repositorio->atualizar($habito, $this->comFrequenciaValidada($this->paraArray($dados), $habito));
    }

    /** @param array<string, mixed>|DTO $dados */
    public function atualizarDoUsuario(int $usuarioId, int|string $id, array|DTO $dados): Model
    {
        /** @var Habito $habito */
        $habito = $this->buscarDoUsuario($usuarioId, $id);

        return $this->repositorio->atualizar($habito, $this->comFrequenciaValidada($this->paraArray($dados), $habito));
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
     * Completa os dados com a frequência e o dia já salvos (num PATCH parcial)
     * e valida a combinação. Ao virar diário, o dia antigo é apagado.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function comFrequenciaValidada(array $dados, ?Habito $atual = null): array
    {
        $frequencia = Frequencia::from($dados['frequencia'] ?? $atual?->frequencia ?? Frequencia::DIARIA->value);

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
