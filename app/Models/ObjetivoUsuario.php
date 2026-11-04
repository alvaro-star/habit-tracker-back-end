<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObjetivoUsuario extends Model
{
    protected $table = 'objetivo_usuario';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['fk_id_usuario', 'fk_id_categoria'];
}
