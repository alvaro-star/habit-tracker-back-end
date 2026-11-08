<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\NivelService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /**
     * Dragão ativo do usuário (users.fk_id_dragao). Fica fora do Fillable:
     * só o DragaoService o altera, ao criar, ativar ou aposentar um dragão.
     */
    public function dragaoAtivo(): BelongsTo
    {
        return $this->belongsTo(Dragao::class, 'fk_id_dragao');
    }

    /** Nível vitalício (1 a 30) calculado pelo XP com o NivelService; não é uma coluna. */
    public function getNivel(): int
    {
        return app(NivelService::class)->nivelDoXp($this->xp_vitalicio ?? 0);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'xp_vitalicio' => 'integer',
            'fk_id_dragao' => 'integer',
            'ultimo_acesso' => 'datetime',
            'streak_atual' => 'integer',
        ];
    }
}
