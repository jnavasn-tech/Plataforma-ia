<?php

namespace App\Enums;

enum TipoRecurso: string
{
    case Lectura = 'lectura';
    case Video = 'video';
    case Articulo = 'articulo';
    case Ejercicio = 'ejercicio';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Lectura => 'Lectura interna',
            self::Video => 'Video',
            self::Articulo => 'Artículo externo',
            self::Ejercicio => 'Ejercicio práctico',
        };
    }
}
