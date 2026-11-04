<?php

namespace App\Models;

use App\Enums\StatusDragao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dragao extends Model
{
    protected $table = 'dragao';

    public $timestamps = false;

    protected $fillable = [
        'fk_id_usuario',
        'fk_id_ovo',
        'fk_id_estagio',
        'nome',
        'xp_ciclo',
        'status',
        'data_nascimento',
        'data_termino',
        'arte_final_url',
        'tempo_total_dias',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusDragao::class,
            'data_nascimento' => 'date',
            'data_termino' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_id_usuario');
    }

    public function ovo(): BelongsTo
    {
        return $this->belongsTo(Ovo::class, 'fk_id_ovo');
    }

    public function estagio(): BelongsTo
    {
        return $this->belongsTo(Estagio::class, 'fk_id_estagio');
    }

    public function escolhasElemento(): HasMany
    {
        return $this->hasMany(EscolhaElemento::class, 'fk_id_dragao');
    }

    public function xpsDiarios(): HasMany
    {
        return $this->hasMany(XpDiario::class, 'fk_id_dragao');
    }
}
