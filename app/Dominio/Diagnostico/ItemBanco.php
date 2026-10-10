<?php

namespace App\Dominio\Diagnostico;

/** Ítem disponible en el banco de preguntas de diagnóstico (dato inmutable, sin dependencias del framework). */
final class ItemBanco
{
    public function __construct(
        public readonly int $idPregunta,
        public readonly string $codigoArea,
        public readonly int $dificultad,
    ) {
    }
}
