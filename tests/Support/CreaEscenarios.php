<?php

namespace Tests\Support;

use App\Enums\TipoDiagnostico;
use App\Models\Diagnostico;
use App\Models\OpcionRespuesta;
use App\Models\RutaModulo;
use App\Models\Usuario;
use App\Services\Diagnostico\ServicioDiagnostico;
use Database\Seeders\CatalogoSeeder;

/** Utilidades compartidas por las pruebas de integración y de sistema. */
trait CreaEscenarios
{
    protected function sembrarCatalogo(): void
    {
        $this->seed(CatalogoSeeder::class);
    }

    protected function aspirante(): Usuario
    {
        return Usuario::factory()->create();
    }

    protected function administrador(): Usuario
    {
        return Usuario::factory()->administrador()->create();
    }

    /**
     * Completa un diagnóstico mediante el servicio. $acierto decide, para cada reactivo,
     * si se elige la opción correcta: bool fijo o callable(int $orden, string $codigoArea): bool.
     */
    protected function completarDiagnostico(Usuario $usuario, bool|callable $acierto = true, TipoDiagnostico $tipo = TipoDiagnostico::Pretest): Diagnostico
    {
        $servicio = app(ServicioDiagnostico::class);
        $diagnostico = $servicio->iniciarOReanudar($usuario, $tipo);

        while (($item = $servicio->itemActual($diagnostico->fresh())) !== null) {
            $correcta = is_callable($acierto) ? $acierto($item->orden, $item->pregunta->area->codigo) : $acierto;
            $opcion = OpcionRespuesta::where('id_pregunta', $item->id_pregunta)->where('es_correcta', $correcta)->firstOrFail();
            $servicio->responder($diagnostico, $item->id_respuesta, $opcion->id_opcion);
        }

        return $diagnostico->fresh(['perfil.ruta.modulos']);
    }

    /** Marca como completados todos los recursos activos de un módulo de la ruta mediante HTTP. */
    protected function completarRecursos(Usuario $usuario, RutaModulo $rutaModulo): void
    {
        foreach ($rutaModulo->modulo->recursosActivos()->get() as $recurso) {
            $this->actingAs($usuario)->post(route('aspirante.recurso.completar', [$rutaModulo, $recurso]))->assertRedirect();
        }
    }

    /** Respuestas de la evaluación del módulo: todas correctas o todas incorrectas. */
    protected function respuestasEvaluacion(RutaModulo $rutaModulo, bool $correctas): array
    {
        $respuestas = [];
        foreach ($rutaModulo->modulo->preguntasEvaluacion()->get() as $pregunta) {
            $respuestas[$pregunta->id_pregunta] = OpcionRespuesta::where('id_pregunta', $pregunta->id_pregunta)->where('es_correcta', $correctas)->value('id_opcion');
        }

        return $respuestas;
    }

    /** Respuesta simulada de la API de Chat Completions con contenido JSON. */
    protected function respuestaOpenAI(array|string $contenido, array $extra = []): array
    {
        return array_replace_recursive([
            'id' => 'chatcmpl-prueba',
            'object' => 'chat.completion',
            'model' => 'gpt-4o-mini',
            'choices' => [[
                'index' => 0,
                'finish_reason' => 'stop',
                'message' => ['role' => 'assistant', 'content' => is_string($contenido) ? $contenido : json_encode($contenido), 'refusal' => null],
            ]],
            'usage' => ['prompt_tokens' => 420, 'completion_tokens' => 180, 'total_tokens' => 600],
        ], $extra);
    }

    protected function habilitarIA(): void
    {
        config([
            'ia.habilitada' => true,
            'ia.openai.api_key' => 'clave-de-prueba-no-real',
            'ia.reintentos' => 1,
            'ia.espera_reintento_ms' => 0,
        ]);
    }
}
