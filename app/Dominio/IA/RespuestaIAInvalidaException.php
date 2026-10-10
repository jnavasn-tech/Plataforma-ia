<?php

namespace App\Dominio\IA;

use RuntimeException;

/** La respuesta del modelo no cumple el contrato JSON o las reglas de negocio; se activa el fallback. */
final class RespuestaIAInvalidaException extends RuntimeException
{
    public function __construct(public readonly string $codigo, string $mensaje)
    {
        parent::__construct($mensaje);
    }
}
