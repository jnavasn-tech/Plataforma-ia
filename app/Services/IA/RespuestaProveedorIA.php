<?php

namespace App\Services\IA;

/** Respuesta cruda del proveedor (contenido JSON en texto y uso de tokens). */
final class RespuestaProveedorIA
{
    public function __construct(
        public readonly string $contenido,
        public readonly string $modelo,
        public readonly ?int $tokensEntrada,
        public readonly ?int $tokensSalida,
        public readonly int $intentos,
    ) {
    }
}
