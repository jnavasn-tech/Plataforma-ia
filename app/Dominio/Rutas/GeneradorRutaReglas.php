<?php

namespace App\Dominio\Rutas;

use App\Dominio\Perfil\ClasificadorNivel;
use App\Enums\NivelCompetencia;
use App\Enums\Prioridad;

/**
 * Generación determinística de rutas de aprendizaje (RF-04, CU-03, mecanismo de fallback).
 *
 * Reglas:
 *  - Área en nivel básico: módulos de nivel básico e intermedio, prioridad alta.
 *  - Área en nivel intermedio: módulos de nivel intermedio y avanzado, prioridad media.
 *  - Área en nivel avanzado: no se incluye, salvo que todas las áreas sean avanzadas;
 *    en ese caso se genera una ruta de desarrollo avanzado opcional (prioridad baja).
 *  - Orden: prioridad, luego menor puntaje (mayor brecha), luego orden del catálogo de áreas.
 *  - Solo se consideran módulos con al menos un recurso activo.
 */
final class GeneradorRutaReglas
{
    public function __construct(
        private readonly ClasificadorNivel $clasificador,
        private readonly int $maxModulos = 10,
    ) {
    }

    /**
     * @param  array<string,float>  $puntajes código de área => puntaje (en el orden del catálogo)
     * @param  array<string,string>  $nombresAreas código => nombre
     * @param  ModuloCatalogo[]  $catalogo
     *
     * @throws CatalogoInsuficienteException
     */
    public function generar(array $puntajes, array $nombresAreas, array $catalogo): PlanRuta
    {
        $ordenCatalogo = array_flip(array_keys($puntajes));
        $todasAvanzadas = $this->todasAvanzadas($puntajes);

        $areas = [];
        foreach ($puntajes as $codigo => $puntaje) {
            $nivel = $this->clasificador->clasificar($puntaje);
            $niveles = match ($nivel) {
                NivelCompetencia::Basico => [NivelCompetencia::Basico, NivelCompetencia::Intermedio],
                NivelCompetencia::Intermedio => [NivelCompetencia::Intermedio, NivelCompetencia::Avanzado],
                NivelCompetencia::Avanzado => $todasAvanzadas ? [NivelCompetencia::Avanzado] : [],
            };
            if ($niveles === []) {
                continue;
            }
            $areas[] = [
                'codigo' => $codigo,
                'puntaje' => $puntaje,
                'nivel' => $nivel,
                'prioridad' => $this->clasificador->prioridad($puntaje),
                'niveles' => $niveles,
            ];
        }

        usort($areas, function (array $a, array $b) use ($ordenCatalogo) {
            return [$a['prioridad']->orden(), $a['puntaje'], $ordenCatalogo[$a['codigo']]]
                <=> [$b['prioridad']->orden(), $b['puntaje'], $ordenCatalogo[$b['codigo']]];
        });

        $planificados = [];
        $orden = 1;
        foreach ($areas as $area) {
            $modulos = array_values(array_filter(
                $catalogo,
                fn (ModuloCatalogo $m) => $m->codigoArea === $area['codigo']
                    && $m->tieneRecursos
                    && in_array($m->nivel, $area['niveles'], true)
            ));
            usort($modulos, fn (ModuloCatalogo $a, ModuloCatalogo $b) => [$a->nivel->orden(), $a->idModulo] <=> [$b->nivel->orden(), $b->idModulo]);

            foreach ($modulos as $modulo) {
                if (count($planificados) >= $this->maxModulos) {
                    break 2;
                }
                $planificados[] = new ModuloPlanificado(
                    $modulo->idModulo,
                    $orden++,
                    $area['prioridad'],
                    $this->justificacion($nombresAreas[$area['codigo']] ?? $area['codigo'], $area['puntaje'], $area['nivel'], $modulo, $todasAvanzadas)
                );
            }
        }

        if ($planificados === []) {
            throw new CatalogoInsuficienteException('No hay módulos con recursos activos para las áreas requeridas por el perfil.');
        }

        [$fortalezas, $mejoras] = $this->fortalezasYMejoras($puntajes, $nombresAreas);

        return new PlanRuta(
            $planificados,
            $this->narrativa($fortalezas, $mejoras, $todasAvanzadas),
            $fortalezas,
            $mejoras,
            $todasAvanzadas
        );
    }

    /**
     * Áreas que obligatoriamente deben estar cubiertas por la ruta (nivel básico).
     * Se usa también para validar las rutas propuestas por la IA.
     *
     * @param  array<string,float>  $puntajes
     * @return string[]
     */
    public function areasObligatorias(array $puntajes): array
    {
        return array_keys(array_filter(
            $puntajes,
            fn (float $p) => $this->clasificador->clasificar($p) === NivelCompetencia::Basico
        ));
    }

    /** @param array<string,float> $puntajes */
    public function todasAvanzadas(array $puntajes): bool
    {
        foreach ($puntajes as $puntaje) {
            if ($this->clasificador->clasificar($puntaje) !== NivelCompetencia::Avanzado) {
                return false;
            }
        }

        return $puntajes !== [];
    }

    private function justificacion(string $area, float $puntaje, NivelCompetencia $nivel, ModuloCatalogo $modulo, bool $avanzada): string
    {
        if ($avanzada) {
            return sprintf(
                'Ruta de desarrollo avanzado opcional: %s ya supera el umbral con %.2f/100; el módulo "%s" permite profundizar.',
                $area, $puntaje, $modulo->nombre
            );
        }

        return sprintf(
            '%s obtuvo %.2f/100 (nivel %s). El módulo "%s" (nivel %s) contribuye a cerrar una brecha de %.2f puntos.',
            $area, $puntaje, mb_strtolower($nivel->etiqueta()), $modulo->nombre,
            mb_strtolower($modulo->nivel->etiqueta()), $this->clasificador->brecha($puntaje)
        );
    }

    /**
     * @param  array<string,float>  $puntajes
     * @param  array<string,string>  $nombres
     * @return array{0:string[],1:string[]}
     */
    private function fortalezasYMejoras(array $puntajes, array $nombres): array
    {
        // Las áreas de mejora se listan de la mayor a la menor brecha (menor puntaje primero).
        asort($puntajes);
        $fortalezas = [];
        $mejoras = [];
        foreach ($puntajes as $codigo => $puntaje) {
            $texto = sprintf('%s (%.2f/100)', $nombres[$codigo] ?? $codigo, $puntaje);
            if ($this->clasificador->clasificar($puntaje) === NivelCompetencia::Avanzado) {
                $fortalezas[] = $texto;
            } else {
                $mejoras[] = $texto;
            }
        }

        return [$fortalezas, $mejoras];
    }

    /** @param string[] $fortalezas @param string[] $mejoras */
    private function narrativa(array $fortalezas, array $mejoras, bool $avanzada): string
    {
        if ($avanzada) {
            return 'Felicitaciones: su perfil supera el umbral en todas las áreas evaluadas. '
                .'Se propone una ruta de desarrollo avanzado opcional para mantener y ampliar sus competencias.';
        }

        $texto = 'Su ruta prioriza las áreas con mayor brecha, de mayor a menor necesidad: '.implode('; ', $mejoras).'.';
        if ($fortalezas !== []) {
            $texto .= ' Sus fortalezas actuales son: '.implode('; ', $fortalezas).'.';
        }

        return $texto.' Complete los recursos de cada módulo y su evaluación para avanzar.';
    }
}
