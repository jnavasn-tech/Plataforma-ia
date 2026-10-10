<?php

namespace App\Enums;

/** Estados del aspirante en el proceso de selección (CU-05). La decisión es exclusivamente humana. */
enum EstadoPostulacion: string
{
    case Pendiente = 'pendiente';
    case EnFormacion = 'en_formacion';
    case Apto = 'apto';
    case NoApto = 'no_apto';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnFormacion => 'En formación',
            self::Apto => 'Apto',
            self::NoApto => 'No apto',
        };
    }

    public function requiereNotificacion(): bool
    {
        return in_array($this, [self::Apto, self::NoApto], true);
    }
}
