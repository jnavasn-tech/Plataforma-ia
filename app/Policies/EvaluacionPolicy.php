<?php

namespace App\Policies;

use App\Models\Evaluacion;
use App\Models\Usuario;

class EvaluacionPolicy
{
    public function ver(Usuario $usuario, Evaluacion $evaluacion): bool
    {
        return $usuario->esAdministrador() || $evaluacion->id_usuario === $usuario->id_usuario;
    }
}
