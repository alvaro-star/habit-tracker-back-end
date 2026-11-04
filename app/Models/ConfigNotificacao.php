<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
