<?php

namespace App\Dominio\Evaluacion;

use InvalidArgumentException;

/**
 * Reglas de la evaluación por módulo (RF-06, CU-04 flujo alterno "actividad de verificación no superada").
 *  - Calificación = aciertos / total × 100.
 *  - Aprobado cuando la calificación es >= 70 (umbral configurable).
 *  - Máximo 3 intentos; agotados los intentos se desbloquea el siguiente módulo de todas formas.
 */
final class CalificadorEvaluacion
{
    public function __construct(
        private readonly float $umbralAprobacion = 70.0,
        private readonly int $maxIntentos = 3,
    ) {
        if ($umbralAprobacion <= 0 || $umbralAprobacion > 100 || $maxIntentos < 1) {
            throw new InvalidArgumentException('Parámetros de evaluación no válidos.');
        }
    }

    public function calificar(int $correctas, int $total): float
    {
        if ($total <= 0 || $correctas < 0 || $correctas > $total) {
            throw new InvalidArgumentException('Cantidad de respuestas no válida.');
        }

        return round($correctas / $total * 100, 2);
    }

    public function aprobado(float $calificacion): bool
    {
        return $calificacion >= $this->umbralAprobacion;
    }

    public function puedeIntentar(int $intentosRealizados, bool $yaAprobado): bool
    {
        return ! $yaAprobado && $intentosRealizados < $this->maxIntentos;
    }

    /** El módulo se considera concluido si fue aprobado o si se agotaron los intentos. */
    public function moduloConcluido(int $intentosRealizados, bool $aprobado): bool
    {
        return $aprobado || $intentosRealizados >= $this->maxIntentos;
    }

    public function maxIntentos(): int
    {
        return $this->maxIntentos;
    }

    public function umbral(): float
    {
        return $this->umbralAprobacion;
    }
}
