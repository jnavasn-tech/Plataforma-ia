<?php

namespace App\Dominio\IA;

use RuntimeException;

/** Protección por diseño: impide enviar a la IA una carga útil que contenga datos personales. */
final class DatosPersonalesDetectadosException extends RuntimeException
{
}
