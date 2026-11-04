<?php

namespace App\Models;

use App\Enums\CanalNotificacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GatilhoNotificacao extends Model
{
    protected $table = 'gatilho_notificacao';

    public $timestamps = false;

    protected $fillable = ['tipo', 'canal', 'frequencia', 'texto_base'];

    protected function casts(): array
    {
        return [
            'canal' => CanalNotificacao::class,
        ];
    }

    public function configuracoes(): HasMany
    {
        return $this->hasMany(ConfigNotificacao::class, 'fk_id_gatilho');
    }
}
