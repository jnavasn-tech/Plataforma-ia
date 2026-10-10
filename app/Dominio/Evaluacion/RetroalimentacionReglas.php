<?php

namespace App\Dominio\Evaluacion;

/**
 * Retroalimentación por reglas (fallback del prompt de retroalimentación, RF-07).
 * Devuelve la misma estructura que la respuesta validada de la IA.
 */
final class RetroalimentacionReglas
{
    /**
     * @param  string[]  $temasAReforzar
     * @return array{mensaje:string,sugerencias:string[],siguiente_paso:string}
     */
    public function generar(string $modulo, float $calificacion, bool $aprobado, int $intento, int $maxIntentos, array $temasAReforzar): array
    {
        $sugerencias = array_map(
            fn (string $tema) => 'Repase el tema: '.mb_substr($tema, 0, 140),
            array_slice($temasAReforzar, 0, 3)
        );

        if ($aprobado && $calificacion >= 90) {
            $mensaje = sprintf('Excelente desempeño en "%s": obtuvo %.2f/100.', $modulo, $calificacion);
            $siguiente = 'Continúe con el siguiente módulo de su ruta.';
        } elseif ($aprobado) {
            $mensaje = sprintf('Aprobó "%s" con %.2f/100. Puede reforzar los temas señalados para consolidar lo aprendido.', $modulo, $calificacion);
            $siguiente = 'Continúe con el siguiente módulo de su ruta.';
        } elseif ($intento < $maxIntentos) {
            $mensaje = sprintf('Obtuvo %.2f/100 en "%s". Le recomendamos revisar el recurso antes de continuar.', $calificacion, $modulo);
            $siguiente = sprintf('Revise los recursos del módulo y realice un nuevo intento (le quedan %d de %d intentos).', $maxIntentos - $intento, $maxIntentos);
        } else {
            $mensaje = sprintf('Obtuvo %.2f/100 en "%s" y completó los %d intentos disponibles.', $calificacion, $modulo, $maxIntentos);
            $siguiente = 'El siguiente módulo se ha desbloqueado; le sugerimos volver a este tema más adelante.';
        }

        if ($sugerencias === []) {
            $sugerencias[] = 'Practique lo aprendido en una tarea real de su entorno.';
        }

        return ['mensaje' => $mensaje, 'sugerencias' => $sugerencias, 'siguiente_paso' => $siguiente];
    }
}
