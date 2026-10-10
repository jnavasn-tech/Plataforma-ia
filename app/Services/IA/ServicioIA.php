<?php

namespace App\Services\IA;

use App\Dominio\IA\DatosPersonalesDetectadosException;
use App\Dominio\IA\EsquemasIA;
use App\Dominio\IA\MinimizadorDatos;
use App\Dominio\IA\RespuestaIAInvalidaException;
use App\Dominio\IA\ValidadorRespuestaIA;
use App\Enums\ClavePrompt;
use App\Enums\EstadoSolicitudIA;
use App\Enums\OrigenGeneracion;
use App\Models\BitacoraIA;
use App\Models\ConfiguracionPrompt;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orquestador de la integración con IA (actor externo de CU-03 y RF-07).
 *
 * 1) Verifica que la IA esté habilitada y configurada.
 * 2) Obtiene la versión activa del prompt institucional.
 * 3) Verifica nuevamente que la entrada no contenga datos personales.
 * 4) Solicita salida JSON estricta, la decodifica y la valida (estructura + reglas de negocio).
 * 5) Ante cualquier fallo aplica el fallback determinístico y lo registra en bitacora_ia,
 *    sin almacenar el prompt, la entrada ni la respuesta.
 */
class ServicioIA
{
    public function __construct(
        private readonly ClienteIA $cliente,
        private readonly ValidadorRespuestaIA $validador,
        private readonly MinimizadorDatos $minimizador,
    ) {
    }

    /**
     * @param  array<string,mixed>  $entrada
     * @param  callable(array):array  $validar reglas de negocio adicionales sobre los datos decodificados
     * @param  callable():array  $fallback generador determinístico con la misma estructura
     */
    public function generar(ClavePrompt $clave, array $entrada, callable $validar, callable $fallback, ?string $referenciaTipo = null, ?int $referenciaId = null): ResultadoIA
    {
        $inicio = hrtime(true);
        $prompt = ConfiguracionPrompt::activo($clave);
        $version = $prompt?->etiquetaVersion() ?? $clave->value.'-archivo';

        if (! config('ia.habilitada') || ! $this->cliente->estaConfigurado()) {
            return $this->usarFallback($clave, $fallback, $inicio, $version, EstadoSolicitudIA::Deshabilitada, 'ia_deshabilitada', 0, $referenciaTipo, $referenciaId);
        }

        try {
            $this->minimizador->asegurarSinDatosPersonales($entrada);
            $respuesta = $this->cliente->solicitarJson(
                $prompt?->contenido ?? $this->promptPorDefecto($clave),
                $entrada,
                EsquemasIA::formatoRespuesta($clave->value)
            );
            $datos = $validar($this->validador->decodificar($respuesta->contenido));
        } catch (ErrorProveedorIAException $e) {
            return $this->usarFallback($clave, $fallback, $inicio, $version, EstadoSolicitudIA::Fallback, $e->codigo, $e->intentos, $referenciaTipo, $referenciaId);
        } catch (RespuestaIAInvalidaException $e) {
            return $this->usarFallback($clave, $fallback, $inicio, $version, EstadoSolicitudIA::Fallback, $e->codigo, 1, $referenciaTipo, $referenciaId);
        } catch (DatosPersonalesDetectadosException) {
            return $this->usarFallback($clave, $fallback, $inicio, $version, EstadoSolicitudIA::Fallback, 'datos_personales_bloqueados', 0, $referenciaTipo, $referenciaId);
        }

        $latencia = $this->milisegundos($inicio);
        $this->registrar([
            'tipo' => $clave->value,
            'estado' => EstadoSolicitudIA::Exito,
            'modelo' => $respuesta->modelo,
            'version_prompt' => $version,
            'latencia_ms' => $latencia,
            'intentos' => $respuesta->intentos,
            'tokens_entrada' => $respuesta->tokensEntrada,
            'tokens_salida' => $respuesta->tokensSalida,
            'referencia_tipo' => $referenciaTipo,
            'referencia_id' => $referenciaId,
        ]);

        return new ResultadoIA($datos, OrigenGeneracion::IA, $version, $respuesta->modelo, $latencia);
    }

    /** Prompt mínimo de respaldo cuando no existe una versión activa en la base de datos. */
    public function promptPorDefecto(ClavePrompt $clave): string
    {
        $ruta = resource_path("prompts/{$clave->value}.v1.md");

        return is_file($ruta) ? (string) file_get_contents($ruta) : 'Responda únicamente con el JSON solicitado.';
    }

    private function usarFallback(ClavePrompt $clave, callable $fallback, int $inicio, string $version, EstadoSolicitudIA $estado, string $codigo, int $intentos, ?string $referenciaTipo, ?int $referenciaId): ResultadoIA
    {
        $datos = $fallback();
        $latencia = $this->milisegundos($inicio);

        $this->registrar([
            'tipo' => $clave->value,
            'estado' => $estado,
            'modelo' => $estado === EstadoSolicitudIA::Deshabilitada ? null : $this->cliente->modelo(),
            'version_prompt' => $version,
            'latencia_ms' => $latencia,
            'intentos' => $intentos,
            'codigo_error' => $codigo,
            'referencia_tipo' => $referenciaTipo,
            'referencia_id' => $referenciaId,
        ]);

        if ($estado === EstadoSolicitudIA::Fallback) {
            Log::channel('ia')->warning('Fallback de IA aplicado', ['tipo' => $clave->value, 'codigo' => $codigo, 'version_prompt' => $version, 'latencia_ms' => $latencia]);
        }

        return new ResultadoIA($datos, OrigenGeneracion::Reglas, $version, null, $latencia, $codigo);
    }

    private function registrar(array $datos): void
    {
        try {
            BitacoraIA::create($datos);
        } catch (Throwable $e) {
            Log::channel('ia')->error('No fue posible registrar bitacora_ia', ['error' => class_basename($e)]);
        }
    }

    private function milisegundos(int $inicio): int
    {
        return (int) round((hrtime(true) - $inicio) / 1_000_000);
    }
}
