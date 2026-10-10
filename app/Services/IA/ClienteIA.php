<?php

namespace App\Services\IA;

/**
 * Contrato del cliente de IA. Desacopla la plataforma del proveedor: puede sustituirse por otro
 * proveedor o por un microservicio externo (por ejemplo, Python/FastAPI) sin cambiar los casos de uso.
 */
interface ClienteIA
{
    /**
     * @param  array<string,mixed>  $entrada datos minimizados que se envían como mensaje de usuario
     * @param  array<string,mixed>  $formatoRespuesta esquema JSON estricto de salida
     *
     * @throws ErrorProveedorIAException
     */
    public function solicitarJson(string $promptSistema, array $entrada, array $formatoRespuesta): RespuestaProveedorIA;

    public function estaConfigurado(): bool;

    public function modelo(): string;
}
