<?php

namespace App\Models;

use App\Enums\Dificuldade;
use App\Enums\TipoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitoModelo extends Model
{
    protected $table = 'habito_modelo';

    public $timestamps = false;

    protected $fillable = [
        'fk_id_categoria',
        'nome',
        'tipo_meta',
        'meta_padrao',
        'unidade_padrao',
        'dificuldade_padrao',
        'prioridade_mvp',
    ];

    protected function casts(): array
    {
        return [
            'tipo_meta' => TipoMeta::class,
            'meta_padrao' => 'decimal:2',
            'dificuldade_padrao' => Dificuldade::class,
            'prioridade_mvp' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'fk_id_categoria');
    }
}
