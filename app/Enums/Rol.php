<?php

namespace App\Enums;

/**
 * Roles de la plataforma (RF-01, RF-08).
 * Se conserva el atributo "rol" de la tabla usuarios definido en el Capítulo IV.
 */
enum Rol: string
{
    case Aspirante = 'aspirante';
    case AdminRrhh = 'admin_rrhh';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Aspirante => 'Aspirante',
            self::AdminRrhh => 'Administrador RRHH',
        };
    }
}
