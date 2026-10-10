<?php

namespace App\Enums;

/** Uso de una pregunta del banco: diagnóstico (CU-02) o evaluación de módulo (RF-06). */
enum UsoPregunta: string
{
    case Diagnostico = 'diagnostico';
    case EvaluacionModulo = 'evaluacion_modulo';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Diagnostico => 'Diagnóstico',
            self::EvaluacionModulo => 'Evaluación de módulo',
        };
    }
}
