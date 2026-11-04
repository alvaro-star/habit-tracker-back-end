<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estagio extends Model
{
    protected $table = 'estagio';

    public $timestamps = false;

    protected $fillable = ['nome', 'ordem', 'xp_marco'];
}
