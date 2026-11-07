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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'data' => 'date:Y-m-d',
            'valor_realizado' => 'decimal:2',
            'percentual' => 'decimal:2',
        ];
    }

    public function habito(): BelongsTo
    {
        return $this->belongsTo(Habito::class, 'fk_id_habito');
    }
}
