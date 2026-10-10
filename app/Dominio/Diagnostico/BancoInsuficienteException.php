<?php

namespace App\Dominio\Diagnostico;

use RuntimeException;

/** Se lanza cuando el banco no tiene ítems suficientes para un área (precondición de CU-02). */
final class BancoInsuficienteException extends RuntimeException
{
    /** @param string[] $areas */
    public function __construct(public readonly array $areas)
    {
        parent::__construct('Banco de preguntas insuficiente para las áreas: '.implode(', ', $areas));
    }
}
