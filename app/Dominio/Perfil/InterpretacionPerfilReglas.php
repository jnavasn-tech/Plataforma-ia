<?php

namespace App\Dominio\Perfil;

use App\Enums\NivelCompetencia;

/**
 * Interpretación del perfil por reglas (fallback del prompt de diagnóstico).
 * Produce la misma estructura que la respuesta validada de la IA.
 */
final class InterpretacionPerfilReglas
{
    public function __construct(private readonly ClasificadorNivel $clasificador)
    {
    }

    /**
     * @param  array<string,float>  $puntajes código => puntaje
     * @param  array<string,string>  $nombres código => nombre del área
     * @return array{resumen:string,fortalezas:string[],areas_mejora:string[],recomendacion_general:string}
     */
    public function generar(array $puntajes, array $nombres, NivelCompetencia $nivelGeneral, float $puntajeGlobal): array
    {
        $fortalezas = [];
        $mejoras = [];
        foreach ($puntajes as $codigo => $puntaje) {
            $nombre = $nombres[$codigo] ?? $codigo;
            $nivel = $this->clasificador->clasificar($puntaje);
            if ($nivel === NivelCompetencia::Avanzado) {
                $fortalezas[] = sprintf('%s: %.2f/100 (nivel %s).', $nombre, $puntaje, mb_strtolower($nivel->etiqueta()));
            } else {
                $mejoras[] = sprintf('%s: %.2f/100 (nivel %s).', $nombre, $puntaje, mb_strtolower($nivel->etiqueta()));
            }
        }

        $resumen = sprintf(
            'El diagnóstico ubica el perfil en nivel %s con un puntaje global de %.2f sobre 100. '
            .'El resultado se calculó con reglas ponderadas por dificultad y sirve para orientar la formación.',
            mb_strtolower($nivelGeneral->etiqueta()),
            $puntajeGlobal
        );

        $recomendacion = $mejoras === []
            ? 'Mantenga sus competencias actualizadas con la ruta de desarrollo avanzado opcional.'
            : 'Inicie la ruta de aprendizaje por las áreas con menor puntaje y avance de forma constante.';

        return [
            'resumen' => $resumen,
            'fortalezas' => $fortalezas,
            'areas_mejora' => $mejoras,
            'recomendacion_general' => $recomendacion,
        ];
    }
}
