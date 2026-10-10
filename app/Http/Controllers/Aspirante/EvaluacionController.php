<?php

namespace App\Http\Controllers\Aspirante;

use App\Dominio\Evaluacion\CalificadorEvaluacion;
use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use App\Models\RutaModulo;
use App\Services\Evaluacion\ServicioEvaluacion;
use App\Services\Rutas\ServicioProgreso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pantallas 14 y 15: evaluación del módulo y retroalimentación. RF-06, RF-07, CU-04. */
class EvaluacionController extends Controller
{
    public function create(RutaModulo $rutaModulo, ServicioProgreso $progreso, CalificadorEvaluacion $calificador): View|RedirectResponse
    {
        $this->authorize('avanzar', $rutaModulo);
        if (! $progreso->evaluacionHabilitada($rutaModulo)) {
            return redirect()->route('aspirante.modulo', $rutaModulo)
                ->with('aviso', 'La evaluación se habilita al completar todos los recursos del módulo.');
        }

        return view('aspirante.evaluacion', [
            'rutaModulo' => $rutaModulo->load('modulo.area'),
            'preguntas' => $rutaModulo->modulo->preguntasEvaluacion()->with('opciones')->get(),
            'intento' => $rutaModulo->evaluaciones()->count() + 1,
            'maxIntentos' => $calificador->maxIntentos(),
            'umbral' => $calificador->umbral(),
        ]);
    }

    public function store(Request $request, RutaModulo $rutaModulo, ServicioEvaluacion $servicio): RedirectResponse
    {
        $this->authorize('avanzar', $rutaModulo);
        $request->validate(['respuestas' => ['required', 'array'], 'respuestas.*' => ['required', 'integer']], [
            'respuestas.required' => 'Por favor responda todas las preguntas.',
        ]);

        $evaluacion = $servicio->registrar($request->user(), $rutaModulo, $request->input('respuestas', []));

        return redirect()->route('aspirante.evaluacion.show', $evaluacion);
    }

    public function show(Evaluacion $evaluacion, ServicioProgreso $progreso, CalificadorEvaluacion $calificador): View
    {
        $this->authorize('ver', $evaluacion);
        $evaluacion->load('retroalimentacion', 'modulo.area', 'rutaModulo.valoracion');

        return view('aspirante.retroalimentacion', [
            'evaluacion' => $evaluacion,
            'puedeReintentar' => $progreso->evaluacionHabilitada($evaluacion->rutaModulo),
            'maxIntentos' => $calificador->maxIntentos(),
        ]);
    }
}
