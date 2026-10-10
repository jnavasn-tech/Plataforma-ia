<?php

namespace App\Dominio\Diagnostico;

/** Respuesta ya registrada en un diagnóstico, utilizada para adaptar la dificultad y calcular puntajes. */
final class RespuestaRegistrada
{
    public function __construct(
        public readonly int $idPregunta,
        public readonly string $codigoArea,
        public readonly int $dificultad,
        public readonly bool $correcta,
    ) {
    }
}
