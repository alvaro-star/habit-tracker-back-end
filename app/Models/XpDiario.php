<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpDiario extends Model
{
    protected $table = 'xp_diario';

    public $timestamps = false;

    protected $fillable = [
        'fk_id_usuario',
        'fk_id_dragao',
        'data',
        'percentual_ponderado',
        'xp_ganho',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'percentual_ponderado' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_id_usuario');
    }

    public function dragao(): BelongsTo
    {
        return $this->belongsTo(Dragao::class, 'fk_id_dragao');
    }
}
