<?php

namespace App\Models;

use App\Enums\ElementoDragao;
use App\Enums\EstagioDragao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscolhaElemento extends Model
{
    protected $table = 'escolha_elemento';

    public $timestamps = false;

    protected $fillable = [
        'fk_id_dragao',
        'elemento',
        // Estágio do dragão no momento da escolha, preenchido pelo EscolhaElementoService.
        'estagio',
        'data_escolha',
    ];

    protected function casts(): array
    {
        return [
            'data_escolha' => 'datetime',
            'estagio' => EstagioDragao::class,
            'elemento' => ElementoDragao::class,
        ];
    }

    public function dragao(): BelongsTo
    {
        return $this->belongsTo(Dragao::class, 'fk_id_dragao');
    }
}
