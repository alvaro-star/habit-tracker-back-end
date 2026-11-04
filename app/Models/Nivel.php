<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nivel extends Model
{
    protected $table = 'nivel';

    public $timestamps = false;

    protected $fillable = ['xp_necessario', 'xp_acumulado'];
}
