<?php

namespace App\Services;

/**
 * Nível vitalício do usuário (MOD5-TASK6 e TASK7), calculado a partir do XP
 * com a curva de config/progression.php, sem tabela no banco.
 */
class NivelService
{
    /**
     * Intervalos de XP de cada nível.
     *
     * O xp_inicial é o XP vitalício a partir do qual o usuário está no nível,
     * e o xp_necessario é o XP desde o nível anterior. O primeiro passo é
     * a = xp_total × crescimento / ((1 + crescimento)^passos − 1), e cada passo
     * seguinte cresce pelo fator (1 + crescimento). Os passos são arredondados,
     * e o último absorve a diferença para fechar o xp_total.
     *
     * @return array<int, array{nivel: int, xp_necessario: int, xp_inicial: int}>
     */
    public function curva(): array
    {
        ['quantidade' => $quantidade, 'xp_total' => $xpTotal, 'crescimento' => $crescimento] = config('progression.niveis');

        $razao = 1 + $crescimento;
        $primeiroPasso = $xpTotal * $crescimento / ($razao ** ($quantidade - 1) - 1);

        $niveis = [['nivel' => 1, 'xp_necessario' => 0, 'xp_inicial' => 0]];
        $acumulado = 0;

        for ($nivel = 2; $nivel <= $quantidade; $nivel++) {
            $necessario = $nivel === $quantidade
                ? $xpTotal - $acumulado
                : (int) round($primeiroPasso * $razao ** ($nivel - 2));
            $acumulado += $necessario;

            $niveis[] = ['nivel' => $nivel, 'xp_necessario' => $necessario, 'xp_inicial' => $acumulado];
        }

        return $niveis;
    }

    /** Nível do XP informado: o maior nível cujo xp_inicial já foi alcançado (1 a 30). */
    public function nivelDoXp(int $xp): int
    {
        $nivelAtual = 1;

        foreach ($this->curva() as $intervalo) {
            if ($xp < $intervalo['xp_inicial']) {
                break;
            }

            $nivelAtual = $intervalo['nivel'];
        }

        return $nivelAtual;
    }
}
