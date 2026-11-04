<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categoria';

    public $timestamps = false;

    protected $fillable = ['nome'];

    public function habitos(): HasMany
    {
        return $this->hasMany(Habito::class, 'fk_id_categoria');
    }
}
