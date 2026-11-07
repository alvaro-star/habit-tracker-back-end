<?php

namespace App\Enums;

/**
 * Estágios de crescimento do dragão pelo XP do ciclo (ESCOPO.md, seção 4.2).
 * O estágio fica em dragao.estagio e é atualizado sempre que o xp_ciclo muda.
 */
enum EstagioDragao: string
{
    case OVO = 'OVO';
    case FILHOTE = 'FILHOTE';
    case JOVEM = 'JOVEM';
    case ADULTO = 'ADULTO';
    case ADULTO_FINAL = 'ADULTO_FINAL';

    /**
     * XP do ciclo em que o usuário escolhe o elemento (Marco 4, mudança de
     * ambiente). Não é um estágio: o dragão continua ADULTO até os 9.000 XP.
     */
    public const XP_ESCOLHA_ELEMENTO = 6000;

    /** XP do ciclo a partir do qual o dragão está neste estágio. */
    public function xpMarco(): int
    {
        return match ($this) {
            self::OVO => 0,
            self::FILHOTE => 120,
            self::JOVEM => 1000,
            self::ADULTO => 3500,
            self::ADULTO_FINAL => 9000,
        };
    }

    /** Estágio do dragão com o XP do ciclo informado: o maior marco já alcançado. */
    public static function doXp(int $xpCiclo): self
    {
        $estagio = self::OVO;

        foreach (self::cases() as $candidato) {
            if ($xpCiclo >= $candidato->xpMarco()) {
                $estagio = $candidato;
            }
        }

        return $estagio;
    }
}
