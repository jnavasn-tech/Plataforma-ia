<?php

namespace App\Dominio\Rutas;

use App\Enums\Prioridad;

/** Módulo asignado dentro de una ruta, con su orden, prioridad y justificación. */
final class ModuloPlanificado
{
    public function __construct(
        public readonly int $idModulo,
        public readonly int $orden,
        public readonly Prioridad $prioridad,
        public readonly string $justificacion,
    ) {
    }
}
