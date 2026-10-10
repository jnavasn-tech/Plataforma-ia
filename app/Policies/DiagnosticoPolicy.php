<?php

namespace App\Policies;

use App\Models\Diagnostico;
use App\Models\Usuario;

class DiagnosticoPolicy
{
    /** El aspirante solo puede ver y responder sus propios diagnósticos; RRHH puede consultarlos. */
    public function ver(Usuario $usuario, Diagnostico $diagnostico): bool
    {
        return $usuario->esAdministrador() || $diagnostico->id_usuario === $usuario->id_usuario;
    }

    public function responder(Usuario $usuario, Diagnostico $diagnostico): bool
    {
        return $usuario->esAspirante() && $diagnostico->id_usuario === $usuario->id_usuario;
    }
}
