<?php

namespace App\Dominio\Perfil;

use App\Enums\NivelCompetencia;
use App\Enums\Prioridad;
use InvalidArgumentException;

/**
 * Clasificación del nivel de competencia (RF-03).
 * Escala por defecto: básico < 50; intermedio 50 a 79.99; avanzado >= 80.
 * Los umbrales son configurables (config/plataforma.php) y deben validarse con expertos.
 */
final class ClasificadorNivel
{
    public function __construct(
        private readonly float $umbralIntermedio = 50.0,
        private readonly float $umbralAvanzado = 80.0,
    ) {
        if ($umbralIntermedio <= 0 || $umbralAvanzado <= $umbralIntermedio || $umbralAvanzado > 100) {
            throw new InvalidArgumentException('Los umbrales de clasificación no son válidos.');
        }
    }

    public function clasificar(float $puntaje): NivelCompetencia
    {
        $this->validar($puntaje);

        return match (true) {
            $puntaje >= $this->umbralAvanzado => NivelCompetencia::Avanzado,
            $puntaje >= $this->umbralIntermedio => NivelCompetencia::Intermedio,
            default => NivelCompetencia::Basico,
        };
    }

    /** Prioridad formativa del área: alta (básico), media (intermedio), baja (avanzado). */
    public function prioridad(float $puntaje): Prioridad
    {
        return match ($this->clasificar($puntaje)) {
            NivelCompetencia::Basico => Prioridad::Alta,
            NivelCompetencia::Intermedio => Prioridad::Media,
            NivelCompetencia::Avanzado => Prioridad::Baja,
        };
    }

    /** Brecha respecto del umbral de nivel avanzado (perfil objetivo). */
    public function brecha(float $puntaje): float
    {
        $this->validar($puntaje);

        return round(max(0.0, $this->umbralAvanzado - $puntaje), 2);
    }

    public function umbralAvanzado(): float
    {
        return $this->umbralAvanzado;
    }

    private function validar(float $puntaje): void
    {
        if ($puntaje < 0 || $puntaje > 100 || is_nan($puntaje)) {
            throw new InvalidArgumentException('El puntaje debe estar entre 0 y 100.');
        }
    }
}
