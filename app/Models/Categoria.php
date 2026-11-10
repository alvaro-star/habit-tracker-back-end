<?php

namespace App\Models;

use Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    /** @use HasFactory<CategoriaFactory> */
    use HasFactory;

    protected $table = 'categoria';

    public $timestamps = false;

    protected $fillable = ['nome'];

    public function habitos(): HasMany
    {
        return $this->hasMany(Habito::class, 'fk_id_categoria');
    }
}
