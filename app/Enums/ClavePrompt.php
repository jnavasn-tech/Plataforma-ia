<?php

namespace App\Enums;

/** Prompts institucionales versionados (RF-08). */
enum ClavePrompt: string
{
    case Diagnostico = 'diagnostico';
    case Ruta = 'ruta';
    case Retroalimentacion = 'retroalimentacion';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Diagnostico => 'Interpretación del diagnóstico',
            self::Ruta => 'Ruta de aprendizaje',
            self::Retroalimentacion => 'Retroalimentación de módulo',
        };
    }
}
