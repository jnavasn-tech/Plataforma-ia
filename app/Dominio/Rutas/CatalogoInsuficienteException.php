<?php

namespace App\Dominio\Rutas;

use RuntimeException;

/** No existen módulos con recursos activos para las áreas que requiere el perfil (precondición de CU-03). */
final class CatalogoInsuficienteException extends RuntimeException
{
}
