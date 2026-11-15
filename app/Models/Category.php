<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'categoria';

    public $timestamps = false;

    protected $fillable = ['nome'];

    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class, 'fk_id_categoria');
    }
}
