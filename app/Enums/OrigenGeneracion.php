<?php

namespace App\Enums;

/** Origen de un contenido generado: modelo de IA o reglas determinísticas (fallback). */
enum OrigenGeneracion: string
{
    case IA = 'ia';
    case Reglas = 'reglas';

    public function etiqueta(): string
    {
        return match ($this) {
            self::IA => 'Generado con IA (revisado por reglas de validación)',
            self::Reglas => 'Generado por reglas predefinidas',
        };
    }
}
