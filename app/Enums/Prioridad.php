<?php

namespace App\Enums;

enum Prioridad: string
{
    case Alta = 'alta';
    case Media = 'media';
    case Baja = 'baja';

    public function orden(): int
    {
        return match ($this) {
            self::Alta => 1,
            self::Media => 2,
            self::Baja => 3,
        };
    }

    public function etiqueta(): string
    {
        return ucfirst($this->value);
    }
}
