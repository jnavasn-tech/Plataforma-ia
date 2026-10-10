<?php

namespace App\Enums;

/** Tipo de aplicación del diagnóstico: pretest (inicial) o postest (final), indicador pretest-postest. */
enum TipoDiagnostico: string
{
    case Pretest = 'pretest';
    case Postest = 'postest';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pretest => 'Diagnóstico inicial (pretest)',
            self::Postest => 'Diagnóstico final (postest)',
        };
    }
}
