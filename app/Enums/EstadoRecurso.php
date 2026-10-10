<?php

namespace App\Enums;

/** Estados del recurso en el catálogo (CU-07). */
enum EstadoRecurso: string
{
    case Activo = 'activo';
    case PendienteVerificacion = 'pendiente_verificacion';
    case Inactivo = 'inactivo';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::PendienteVerificacion => 'Pendiente de verificación',
            self::Inactivo => 'Inactivo',
        };
    }
}
