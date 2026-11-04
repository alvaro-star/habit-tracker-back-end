<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscolhaElemento extends Model
{
    protected $table = 'escolha_elemento';

    public $timestamps = false;

    protected $fillable = [
        'fk_id_dragao',
        'fk_id_elemento',
        'fk_id_estagio',
        'data_escolha',
    ];

    protected function casts(): array
    {
        return [
            'data_escolha' => 'datetime',
        ];
    }

    public function dragao(): BelongsTo
    {
        return $this->belongsTo(Dragao::class, 'fk_id_dragao');
    }

    public function elemento(): BelongsTo
    {
        return $this->belongsTo(Elemento::class, 'fk_id_elemento');
    }

    public function estagio(): BelongsTo
    {
        return $this->belongsTo(Estagio::class, 'fk_id_estagio');
    }
}
