<?php

namespace App\Http\Requests\CheckIns;

use App\DTOs\CheckIns\RegistroHabitoDTO;
use App\Enums\StatusRegistro;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/** Corpo do POST de check-in, que cria ou atualiza o registro do hábito no dia. */
class RegistroHabitoRequest extends ApiRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        // Só atende o POST (não há PUT nem DELETE de check-in). Ao desmarcar, só valem o hábito e a data.
        $desmarcando = fn () => ! $this->boolean('checked');

        return [
            /** true marca o hábito no dia (cria ou atualiza o check-in); false desmarca (exclui o check-in do dia). */
            'checked' => ['required', 'boolean'],
            /** Hábito do check-in; precisa pertencer ao usuário. Junto com a `data`, identifica o registro do dia. */
            'fk_id_habito' => ['required', 'integer', Rule::exists('habito', 'id')->where('fk_id_usuario', $this->user()?->id)],
            /** Dia do check-in (AAAA-MM-DD). Se o hábito já tem check-in nesse dia, ele é atualizado. */
            'data' => ['required', 'date_format:Y-m-d'],
            /** Só vale em hábitos de REALIZACAO (sem ele, NAO_CONCLUIDO). Em TEMPO e QUANTIDADE, o status é calculado pelo valor_realizado. */
            'status' => [Rule::excludeIf($desmarcando), 'nullable', Rule::enum(StatusRegistro::class)],
            /** Minutos (TEMPO) ou quantidade (QUANTIDADE) feitos no dia, comparados com o valor_meta do hábito. */
            'valor_realizado' => [Rule::excludeIf($desmarcando), 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
            /** Só vale em hábitos de REALIZACAO com status EM_ANDAMENTO; nos demais casos é calculado. */
            'percentual' => [Rule::excludeIf($desmarcando), 'sometimes', 'numeric', 'between:0,100'],
        ];
    }

    public function toDto(): RegistroHabitoDTO
    {
        return RegistroHabitoDTO::fromArray($this->validated());
    }
}
