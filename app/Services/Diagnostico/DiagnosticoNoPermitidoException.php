<?php

namespace App\Services\Diagnostico;

use RuntimeException;

/** Precondición de CU-02 no satisfecha (por ejemplo, diagnóstico ya completado en el período). */
final class DiagnosticoNoPermitidoException extends RuntimeException
{
}
