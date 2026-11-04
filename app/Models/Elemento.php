<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Elemento extends Model
{
    protected $table = 'elemento';

    public $timestamps = false;

    protected $fillable = ['nome', 'cenario_url'];

    public function escolhasElemento(): HasMany
    {
        return $this->hasMany(EscolhaElemento::class, 'fk_id_elemento');
    }
}
