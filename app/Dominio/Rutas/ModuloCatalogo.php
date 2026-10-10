<?php

namespace App\Dominio\Rutas;

use App\Enums\NivelCompetencia;

/** Módulo del catálogo disponible para conformar rutas (dato inmutable). */
final class ModuloCatalogo
{
    public function __construct(
        public readonly int $idModulo,
        public readonly string $codigoArea,
        public readonly string $nombre,
        public readonly NivelCompetencia $nivel,
        public readonly bool $tieneRecursos = true,
    ) {
    }
}
