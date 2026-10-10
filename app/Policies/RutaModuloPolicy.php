<?php

namespace App\Policies;

use App\Models\RutaModulo;
use App\Models\Usuario;

class RutaModuloPolicy
{
    /** Solo el aspirante dueño de la ruta puede avanzar en sus módulos. */
    public function avanzar(Usuario $usuario, RutaModulo $rutaModulo): bool
    {
        return $usuario->esAspirante() && $rutaModulo->ruta->propietarioId() === $usuario->id_usuario;
    }
}
