<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\RedefinicaoSenha;
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
    /** @return BelongsTo<Dragao, $this> */
    public function dragaoAtivo(): BelongsTo
    {
        return $this->belongsTo(Dragao::class, 'fk_id_dragao');
    }

    /** Nível vitalício (1 a 30) calculado pelo XP com o NivelService; não é uma coluna. */
    public function getNivel(): int
    {
        return app(NivelService::class)->nivelDoXp($this->xp_vitalicio ?? 0);
    }

    /**
     * Notificação de redefinição de senha para API.
     *
     * Como não há rota web de redefinição (projeto somente API), o e-mail
     * envia apenas o token para o front-end usar em POST /api/auth/reset-password.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RedefinicaoSenha($token));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'xp_vitalicio' => 'integer',
            'fk_id_dragao' => 'integer',
            'ultimo_acesso' => 'datetime',
            'ultimo_acesso_at' => 'datetime',
            'onboarding_concluido' => 'boolean',
            'streak_atual' => 'integer',
        ];
    }
}
