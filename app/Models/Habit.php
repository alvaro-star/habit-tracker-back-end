<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habit extends Model
{
    protected $table = 'habito';

    public $timestamps = false;

    const CREATED_AT = 'data_criacao';

    const UPDATED_AT = null;

    protected $fillable = [
        'fk_id_usuario',
        'fk_id_categoria',
        'nome',
        'polaridade',
        'tipo_meta',
        'frequencia',
        'valor_meta',
        'unidade',
        'dificuldade',
        'peso',
        'ativo',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'fk_id_categoria');
    }

    public function records(): HasMany
    {
        return $this->hasMany(HabitRecord::class, 'fk_id_habito');
    }
}
