<?php

namespace App\Services\IA;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Cliente HTTP para la API de Chat Completions de OpenAI con salida estructurada (json_schema estricto),
 * tiempo de espera y reintentos controlados. La clave se lee de la configuración (variable OPENAI_API_KEY).
 */
class ClienteOpenAI implements ClienteIA
{
    public function estaConfigurado(): bool
    {
        return filled(config('ia.openai.api_key'));
    }

    public function modelo(): string
    {
        return (string) config('ia.modelo');
    }

    public function solicitarJson(string $promptSistema, array $entrada, array $formatoRespuesta): RespuestaProveedorIA
    {
        if (! $this->estaConfigurado()) {
            throw new ErrorProveedorIAException('sin_configuracion', 'No se ha configurado la clave del proveedor de IA.', 0);
        }

        $cuerpo = [
            'model' => $this->modelo(),
            'temperature' => (float) config('ia.temperatura'),
            'max_tokens' => (int) config('ia.max_tokens'),
            'response_format' => $formatoRespuesta,
            'messages' => [
                ['role' => 'system', 'content' => $promptSistema],
                ['role' => 'user', 'content' => json_encode($entrada, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)],
            ],
        ];

        $maxIntentos = 1 + max(0, (int) config('ia.reintentos'));
        $ultimoCodigo = 'desconocido';

        for ($intento = 1; $intento <= $maxIntentos; $intento++) {
            try {
                $respuesta = $this->cliente()->post('/chat/completions', $cuerpo);
            } catch (ConnectionException $e) {
                $ultimoCodigo = str_contains(mb_strtolower($e->getMessage()), 'timed out') || str_contains($e->getMessage(), 'cURL error 28') ? 'timeout' : 'conexion';
                $this->esperar($intento, $maxIntentos);

                continue;
            }

            if ($respuesta->successful()) {
                return $this->interpretar($respuesta, $intento);
            }

            $ultimoCodigo = 'http_'.$respuesta->status();
            if (! $this->esTransitorio($respuesta)) {
                throw new ErrorProveedorIAException($ultimoCodigo, 'El proveedor de IA rechazó la solicitud.', $intento);
            }
            $this->esperar($intento, $maxIntentos);
        }

        throw new ErrorProveedorIAException($ultimoCodigo, 'El proveedor de IA no respondió correctamente.', $maxIntentos);
    }

    private function cliente()
    {
        $cliente = Http::baseUrl(rtrim((string) config('ia.openai.url_base'), '/'))
            ->withToken((string) config('ia.openai.api_key'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout((int) config('ia.timeout'));

        if (filled(config('ia.openai.organizacion'))) {
            $cliente = $cliente->withHeaders(['OpenAI-Organization' => config('ia.openai.organizacion')]);
        }

        return $cliente;
    }

    private function interpretar(Response $respuesta, int $intentos): RespuestaProveedorIA
    {
        $eleccion = $respuesta->json('choices.0');
        if (! is_array($eleccion)) {
            throw new ErrorProveedorIAException('respuesta_vacia', 'El proveedor devolvió una respuesta sin contenido.', $intentos);
        }
        if (filled($eleccion['message']['refusal'] ?? null)) {
            throw new ErrorProveedorIAException('rechazo_modelo', 'El modelo rechazó generar la respuesta.', $intentos);
        }
        if (($eleccion['finish_reason'] ?? null) === 'length') {
            throw new ErrorProveedorIAException('respuesta_truncada', 'La respuesta del modelo quedó truncada.', $intentos);
        }
        $contenido = $eleccion['message']['content'] ?? null;
        if (! is_string($contenido) || trim($contenido) === '') {
            throw new ErrorProveedorIAException('respuesta_vacia', 'El proveedor devolvió un contenido vacío.', $intentos);
        }

        return new RespuestaProveedorIA(
            $contenido,
            (string) ($respuesta->json('model') ?? $this->modelo()),
            $respuesta->json('usage.prompt_tokens'),
            $respuesta->json('usage.completion_tokens'),
            $intentos,
        );
    }

    private function esTransitorio(Response $respuesta): bool
    {
        return $respuesta->status() === 429 || $respuesta->serverError();
    }

    private function esperar(int $intento, int $maxIntentos): void
    {
        $espera = (int) config('ia.espera_reintento_ms');
        if ($intento < $maxIntentos && $espera > 0) {
            usleep($espera * 1000 * $intento);
        }
    }
}
