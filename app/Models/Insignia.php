<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Insignia extends Model
{
    protected $table = 'insignia';

    public $timestamps = false;

    protected $fillable = ['nome', 'descricao', 'icone_url'];

    public function insigniasUsuario(): HasMany
    {
        return $this->hasMany(UsuarioInsignia::class, 'fk_id_insignia');
    }
}
