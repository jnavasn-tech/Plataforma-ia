<?php

namespace App\Services\IA;

use RuntimeException;

/** Error técnico del proveedor: timeout, conexión, HTTP 4xx/5xx, rechazo o respuesta truncada. */
final class ErrorProveedorIAException extends RuntimeException
{
    public function __construct(public readonly string $codigo, string $mensaje, public readonly int $intentos = 1)
    {
        parent::__construct($mensaje);
    }
}
