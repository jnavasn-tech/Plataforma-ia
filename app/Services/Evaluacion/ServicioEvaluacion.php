<?php

namespace App\Services\Evaluacion;

use App\Dominio\Evaluacion\CalificadorEvaluacion;
use App\Dominio\Evaluacion\RetroalimentacionReglas;
use App\Dominio\IA\MinimizadorDatos;
use App\Dominio\IA\ValidadorRespuestaIA;
use App\Enums\ClavePrompt;
use App\Models\Evaluacion;
use App\Models\OpcionRespuesta;
use App\Models\Retroalimentacion;
use App\Models\RutaModulo;
use App\Models\Usuario;
use App\Services\Bitacora\RegistradorBitacora;
use App\Services\IA\ServicioIA;
use App\Services\Rutas\ServicioProgreso;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Evaluación al finalizar cada módulo (RF-06) y retroalimentación inteligente (RF-07). */
class ServicioEvaluacion
{
    public function __construct(
        private readonly CalificadorEvaluacion $calificador,
        private readonly ServicioProgreso $progreso,
        private readonly ServicioIA $ia,
        private readonly ValidadorRespuestaIA $validador,
        private readonly MinimizadorDatos $minimizador,
        private readonly RetroalimentacionReglas $reglas,
        private readonly RegistradorBitacora $bitacora,
    ) {
    }

    /**
     * @param  array<int|string,int|string>  $respuestas id_pregunta => id_opcion
     *
     * @throws ValidationException
     */
    public function registrar(Usuario $usuario, RutaModulo $rutaModulo, array $respuestas): Evaluacion
    {
        if (! $this->progreso->evaluacionHabilitada($rutaModulo)) {
            throw ValidationException::withMessages(['evaluacion' => 'La evaluación no está disponible para este módulo.']);
        }

        $modulo = $rutaModulo->modulo;
        $preguntas = $modulo->preguntasEvaluacion()->get();
        if ($preguntas->isEmpty()) {
            throw ValidationException::withMessages(['evaluacion' => 'El módulo no tiene preguntas de evaluación configuradas.']);
        }

        $detalle = [];
        $correctas = 0;
        $temasAReforzar = [];
        foreach ($preguntas as $pregunta) {
            $idOpcion = (int) ($respuestas[$pregunta->id_pregunta] ?? 0);
            $opcion = OpcionRespuesta::where('id_pregunta', $pregunta->id_pregunta)->whereKey($idOpcion)->first();
            if ($opcion === null) {
                throw ValidationException::withMessages(["respuestas.{$pregunta->id_pregunta}" => 'Por favor responda todas las preguntas.']);
            }
            $correctas += $opcion->es_correcta ? 1 : 0;
            if (! $opcion->es_correcta) {
                $temasAReforzar[] = $pregunta->enunciado;
            }
            $detalle[] = ['id_pregunta' => $pregunta->id_pregunta, 'id_opcion' => $opcion->id_opcion, 'correcta' => $opcion->es_correcta];
        }

        $calificacion = $this->calificador->calificar($correctas, $preguntas->count());
        $aprobado = $this->calificador->aprobado($calificacion);

        $evaluacion = DB::transaction(function () use ($usuario, $rutaModulo, $detalle, $calificacion, $aprobado) {
            $intento = $rutaModulo->evaluaciones()->lockForUpdate()->count() + 1;

            return Evaluacion::create([
                'id_modulo' => $rutaModulo->id_modulo,
                'id_usuario' => $usuario->id_usuario,
                'id_ruta_modulo' => $rutaModulo->id_ruta_modulo,
                'intento' => $intento,
                'calificacion' => $calificacion,
                'aprobado' => $aprobado,
                'respuestas_json' => $detalle,
            ]);
        });

        $this->generarRetroalimentacion($evaluacion, $temasAReforzar);

        if ($this->calificador->moduloConcluido($evaluacion->intento, $aprobado)) {
            $this->progreso->concluirModulo($usuario, $rutaModulo);
        }

        $this->bitacora->registrar('evaluacion_registrada', $usuario, $evaluacion, [
            'id_modulo' => $rutaModulo->id_modulo,
            'intento' => $evaluacion->intento,
            'calificacion' => $calificacion,
            'aprobado' => $aprobado,
        ]);

        return $evaluacion;
    }

    /** @param string[] $temasAReforzar */
    private function generarRetroalimentacion(Evaluacion $evaluacion, array $temasAReforzar): Retroalimentacion
    {
        $modulo = $evaluacion->modulo()->with('area')->first();
        $maxIntentos = $this->calificador->maxIntentos();

        $entrada = $this->minimizador->entradaRetroalimentacion(
            $modulo->nombre, $modulo->area->nombre, $modulo->nivel->value,
            $evaluacion->calificacion, $evaluacion->aprobado, $evaluacion->intento, $maxIntentos, $temasAReforzar
        );

        $resultado = $this->ia->generar(
            ClavePrompt::Retroalimentacion,
            $entrada,
            fn (array $datos) => $this->validador->validarRetroalimentacion($datos),
            fn () => $this->reglas->generar($modulo->nombre, $evaluacion->calificacion, $evaluacion->aprobado, $evaluacion->intento, $maxIntentos, $temasAReforzar),
            'evaluacion',
            $evaluacion->id_evaluacion,
        );

        return Retroalimentacion::create([
            'id_evaluacion' => $evaluacion->id_evaluacion,
            'texto_generado' => $resultado->datos['mensaje'],
            'sugerencias_json' => $resultado->datos['sugerencias'],
            'siguiente_paso' => mb_substr($resultado->datos['siguiente_paso'], 0, 300),
            'origen' => $resultado->origen,
            'version_prompt' => $resultado->versionPrompt,
        ]);
    }
}
