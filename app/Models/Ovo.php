<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ovo extends Model
{
    protected $table = 'ovo';

    public $timestamps = false;

    protected $fillable = ['nome', 'imagem_url'];

    public function dragoes(): HasMany
    {
        return $this->hasMany(Dragao::class, 'fk_id_ovo');
    }
}
