<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroHabito extends Model
{
    protected $table = 'registro_habito';

    public $timestamps = false;

    protected $fillable = [
        'fk_id_habito',
        'data',
        'status',
        'valor_realizado',
        'percentual',
    ];

    public function habito(): BelongsTo
    {
        return $this->belongsTo(Habito::class, 'fk_id_habito');
    }
}
