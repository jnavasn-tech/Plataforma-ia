<?php

namespace App\Enums;

enum EstadoRuta: string
{
    case Activa = 'activa';
    case Completada = 'completada';
    case Reemplazada = 'reemplazada';
}
