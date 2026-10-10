<?php

namespace App\Enums;

enum EstadoRutaModulo: string
{
    case Pendiente = 'pendiente';
    case EnCurso = 'en_curso';
    case Completado = 'completado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnCurso => 'En curso',
            self::Completado => 'Completado',
        };
    }
}
