<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habito extends Model
{
    protected $table = 'habito';

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

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'fk_id_categoria');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroHabito::class, 'fk_id_habito');
    }
}
