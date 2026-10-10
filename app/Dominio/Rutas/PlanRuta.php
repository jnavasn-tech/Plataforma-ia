<?php

namespace App\Dominio\Rutas;

/** Resultado de la generación de una ruta (por IA validada o por reglas). */
final class PlanRuta
{
    /**
     * @param  ModuloPlanificado[]  $modulos
     * @param  string[]  $fortalezas
     * @param  string[]  $areasMejora
     */
    public function __construct(
        public readonly array $modulos,
        public readonly string $narrativa,
        public readonly array $fortalezas,
        public readonly array $areasMejora,
        public readonly bool $esRutaAvanzada,
    ) {
    }

    /** @return int[] */
    public function idsModulos(): array
    {
        return array_map(fn (ModuloPlanificado $m) => $m->idModulo, $this->modulos);
    }
}
