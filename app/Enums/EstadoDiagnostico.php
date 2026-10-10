<?php

namespace App\Enums;

/** Estados del diagnóstico según el diagrama de estados del Capítulo IV (Figura 12). */
enum EstadoDiagnostico: string
{
    case Iniciado = 'iniciado';
    case EnProgreso = 'en_progreso';
    case Completado = 'completado';
    case Abandonado = 'abandonado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Iniciado => 'Iniciado',
            self::EnProgreso => 'En progreso',
            self::Completado => 'Completado',
            self::Abandonado => 'Abandonado',
        };
    }

    public function esActivo(): bool
    {
        return in_array($this, [self::Iniciado, self::EnProgreso], true);
    }
}
