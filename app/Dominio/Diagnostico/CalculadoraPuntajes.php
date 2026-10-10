<?php

namespace App\Dominio\Diagnostico;

/**
 * Algoritmo de calificación por área de competencia (RF-02, RF-03).
 *
 * Puntaje del área (0-100) = Σ(dificultad × acierto) / Σ(dificultad) × 100.
 * Cada respuesta pondera según su dificultad (1, 2 o 3), de modo que acertar ítems
 * difíciles aporta más que acertar ítems sencillos. El puntaje global es el promedio
 * simple de las áreas evaluadas (todas las áreas pesan igual).
 */
final class CalculadoraPuntajes
{
    /**
     * @param  RespuestaRegistrada[]  $respuestas
     * @param  string[]  $ordenAreas
     * @return array<string,float> código de área => puntaje
     */
    public function puntajesPorArea(array $respuestas, array $ordenAreas): array
    {
        $resultado = [];
        foreach ($ordenAreas as $area) {
            $resultado[$area] = $this->puntajeArea(array_values(array_filter(
                $respuestas,
                fn (RespuestaRegistrada $r) => $r->codigoArea === $area
            )));
        }

        return $resultado;
    }

    /** @param RespuestaRegistrada[] $respuestasArea */
    public function puntajeArea(array $respuestasArea): float
    {
        $pesoTotal = 0;
        $pesoObtenido = 0;
        foreach ($respuestasArea as $respuesta) {
            $pesoTotal += $respuesta->dificultad;
            if ($respuesta->correcta) {
                $pesoObtenido += $respuesta->dificultad;
            }
        }

        return $pesoTotal === 0 ? 0.0 : round($pesoObtenido / $pesoTotal * 100, 2);
    }

    /** @param array<string,float> $puntajesPorArea */
    public function puntajeGlobal(array $puntajesPorArea): float
    {
        if ($puntajesPorArea === []) {
            return 0.0;
        }

        return round(array_sum($puntajesPorArea) / count($puntajesPorArea), 2);
    }
}
