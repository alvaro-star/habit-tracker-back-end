<?php

namespace App\Models;

use App\Enums\Polaridade;
use App\Enums\StatusRegistro;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\HabitoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habito extends Model
{
    /** @use HasFactory<HabitoFactory> */
    use HasFactory;

    protected $table = 'habito';

    const UPDATED_AT = null;

    protected $fillable = [
        'fk_id_usuario',
        'fk_id_categoria',
        'nome',
        'polaridade',
        'tipo_meta',
        'frequencia',
        'dia_frequencia',
        'valor_meta',
        'unidade',
        'dificuldade',
        'peso',
        'ativo',
        'horario_lembrete',
        // Preenchido pelo HabitoService ao desativar o hábito; não vem da requisição.
        'inactivated_at',
        // Só nos hábitos RUIM: desde quando o usuário está sem praticar (MOD3-TASK4).
        'inicio_abstinencia',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'inactivated_at' => 'datetime',
            'inicio_abstinencia' => 'immutable_datetime',
            'valor_meta' => 'decimal:2',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'fk_id_categoria');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroHabito::class, 'fk_id_habito');
    }

    public function ehRuim(): bool
    {
        return $this->polaridade === Polaridade::RUIM->value;
    }

    /**
     * Data do último check-in em que o usuário praticou o hábito ruim (status
     * diferente de NAO_CONCLUIDO). Usa a coluna ultima_recaida quando a
     * consulta já a trouxe (HabitoRepository), para evitar uma query por hábito.
     */
    public function ultimaRecaida(): ?CarbonImmutable
    {
        if (! $this->ehRuim()) {
            return null;
        }

        $data = array_key_exists('ultima_recaida', $this->attributes)
            ? $this->attributes['ultima_recaida']
            : $this->registros()->where('status', '!=', StatusRegistro::NAO_CONCLUIDO->value)->max('data');

        return $data !== null ? CarbonImmutable::parse($data)->startOfDay() : null;
    }

    /**
     * Contador de abstinência (MOD3-TASK4): dias completos sem praticar o
     * hábito ruim, contados do mais recente entre o inicio_abstinencia e o dia
     * seguinte à última recaída. Uma recaída hoje zera o contador. Nulo nos
     * hábitos bons.
     */
    public function diasSemPraticar(?CarbonInterface $hoje = null): ?int
    {
        if (! $this->ehRuim() || $this->inicio_abstinencia === null) {
            return null;
        }

        $hoje = CarbonImmutable::parse($hoje ?? CarbonImmutable::today())->startOfDay();
        $inicio = $this->inicio_abstinencia->startOfDay();
        $recaida = $this->ultimaRecaida();

        if ($recaida !== null && $recaida->addDay()->gt($inicio)) {
            $inicio = $recaida->addDay();
        }

        return $inicio->gt($hoje) ? 0 : (int) $inicio->diffInDays($hoje);
    }
}
