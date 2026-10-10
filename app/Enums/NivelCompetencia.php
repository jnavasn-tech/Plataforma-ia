<?php

namespace App\Enums;

/** Niveles de competencia usados por el perfil (RF-03) y por los módulos formativos. */
enum NivelCompetencia: string
{
    case Basico = 'basico';
    case Intermedio = 'intermedio';
    case Avanzado = 'avanzado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Basico => 'Básico',
            self::Intermedio => 'Intermedio',
            self::Avanzado => 'Avanzado',
        };
    }

    public function orden(): int
    {
        return match ($this) {
            self::Basico => 1,
            self::Intermedio => 2,
            self::Avanzado => 3,
        };
    }
}
