<?php

namespace App\Repositories;

use App\Models\XpDiario;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class XpDiarioRepository extends BaseRepository
{
    protected string $model = XpDiario::class;

    /** XP registrado do usuário no dia, se houver. */
    public function doUsuarioNoDia(int $usuarioId, CarbonInterface $data): ?XpDiario
    {
        return $this->consultaDoUsuario($usuarioId)
            ->whereDate('data', $data->toDateString())
            ->orderByDesc('id')
            ->first();
    }

    /**
     * XP registrado do usuário entre as duas datas (inclusive), do mais recente para o mais antigo.
     *
     * @return Collection<int, XpDiario>
     */
    public function doUsuarioNoIntervalo(int $usuarioId, CarbonInterface $inicio, CarbonInterface $fim): Collection
    {
        return $this->consultaDoUsuario($usuarioId)
            ->whereDate('data', '>=', $inicio->toDateString())
            ->whereDate('data', '<=', $fim->toDateString())
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->get();
    }
}
