<?php

namespace App\Http\Requests\Habits;

use App\DTOs\Habits\HabitoDTO;
use App\Enums\Dificuldade;
use App\Enums\Frequencia;
use App\Enums\Polaridade;
use App\Enums\TipoMeta;
use App\Http\Requests\ApiRequest;
use App\Rules\DiaFrequenciaValido;
use Illuminate\Validation\Rule;

/**
 * @ignoreSchema O corpo é gerado por operação, porque POST e PUT/PATCH têm campos obrigatórios diferentes.
 */
class HabitoRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obrigatorio = $this->obrigatorio();

        return [
            'fk_id_categoria' => [$obrigatorio, 'integer', 'exists:categoria,id'],
            'nome' => [$obrigatorio, 'string', 'max:80'],
            'polaridade' => [$obrigatorio, Rule::enum(Polaridade::class)],
            'tipo_meta' => [$obrigatorio, Rule::enum(TipoMeta::class)],
            // Num PATCH, enviar o dia_frequencia também exige a frequencia: os dois andam juntos.
            'frequencia' => [$this->exists('dia_frequencia') ? 'required' : $obrigatorio, Rule::enum(Frequencia::class)],
            /**
             * Dia em que o hábito deve ser cumprido; sempre enviado junto com a frequencia.
             * DIARIA: null. SEMANAL: SEG, TER, QUA, QUI, SEX, SAB ou DOM. MENSAL: dia de 1 a 31.
             * Um hábito mensal dos dias 29, 30 ou 31 só aparece nos meses que têm esse dia
             * (ex.: o dia 31 não aparece em abril, junho, setembro, novembro nem fevereiro).
             */
            'dia_frequencia' => ['nullable', new DiaFrequenciaValido],
            'valor_meta' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'unidade' => ['nullable', 'string', 'max:20'],
            'peso' => [$obrigatorio, 'integer', 'min:'.Dificuldade::PESO_MINIMO, 'max:'.Dificuldade::PESO_MAXIMO],
            'ativo' => ['sometimes', 'boolean'],
            // A data de criação é gravada pelo timestamp do banco: não pode ser enviada, com nenhum dos dois nomes.
            'created_at' => ['prohibited'],
            'data_criacao' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $mensagem = 'A data de criação do hábito é definida pelo sistema e não pode ser enviada.';

        return [
            'created_at.prohibited' => $mensagem,
            'data_criacao.prohibited' => $mensagem,
        ];
    }

    public function toDto(): HabitoDTO
    {
        return HabitoDTO::fromArray($this->validated());
    }
}
