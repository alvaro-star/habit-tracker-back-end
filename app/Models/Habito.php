<?php

namespace App\Models;

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
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'inactivated_at' => 'datetime',
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
}
