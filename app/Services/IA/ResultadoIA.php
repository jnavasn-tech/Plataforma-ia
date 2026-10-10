<?php

namespace App\Services\IA;

use App\Enums\OrigenGeneracion;

/** Resultado utilizable por los casos de uso: datos validados y su procedencia (IA o reglas). */
final class ResultadoIA
{
    /** @param array<string,mixed> $datos */
    public function __construct(
        public readonly array $datos,
        public readonly OrigenGeneracion $origen,
        public readonly ?string $versionPrompt,
        public readonly ?string $modelo,
        public readonly int $latenciaMs,
        public readonly ?string $codigoError = null,
    ) {
    }
}
