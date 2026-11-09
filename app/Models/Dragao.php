<?php

namespace App\Models;

use App\Enums\ElementoDragao;
use App\Enums\EstagioDragao;
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
        // Elemento atual (ElementoDragao); cada troca entra na trajetória (escolha_elemento).
        'elemento',
        'nome',
        'xp_ciclo',
        'status',
        // Gerado pelo DragaoService a partir do xp_ciclo; não vem da requisição.
        'estagio',
        'data_nascimento',
        'data_termino',
        'arte_final_url',
        'tempo_total_dias',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusDragao::class,
            'estagio' => EstagioDragao::class,
            'elemento' => ElementoDragao::class,
            'data_nascimento' => 'date',
            'data_termino' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_id_usuario');
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
