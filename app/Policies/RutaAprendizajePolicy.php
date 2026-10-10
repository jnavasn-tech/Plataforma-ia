<?php

namespace App\Policies;

use App\Models\RutaAprendizaje;
use App\Models\Usuario;

class RutaAprendizajePolicy
{
    public function ver(Usuario $usuario, RutaAprendizaje $ruta): bool
    {
        return $usuario->esAdministrador() || $ruta->propietarioId() === $usuario->id_usuario;
    }
}
