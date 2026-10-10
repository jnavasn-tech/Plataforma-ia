<?php

namespace App\Enums;

enum EstadoSolicitudIA: string
{
    case Exito = 'exito';
    case Fallback = 'fallback';
    case Deshabilitada = 'deshabilitada';
}
