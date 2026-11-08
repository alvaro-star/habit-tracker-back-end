<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigNotificacao extends Model
{
    protected $table = 'config_notificacao';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['fk_id_usuario', 'fk_id_gatilho', 'ativo', 'horario'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function gatilho(): BelongsTo
    {
        return $this->belongsTo(GatilhoNotificacao::class, 'fk_id_gatilho');
    }
}
