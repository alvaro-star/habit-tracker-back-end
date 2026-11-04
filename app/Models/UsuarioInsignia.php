<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioInsignia extends Model
{
    protected $table = 'usuario_insignia';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['fk_id_usuario', 'fk_id_insignia', 'data_conquista'];

    protected function casts(): array
    {
        return [
            'data_conquista' => 'datetime',
        ];
    }
}
