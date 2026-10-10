<?php

namespace App\Http\Controllers\Aspirante;

use App\Dominio\Evaluacion\CalificadorEvaluacion;
use App\Http\Controllers\Controller;
use App\Models\ProgresoRecurso;
use App\Models\RutaModulo;
use App\Services\Rutas\ServicioProgreso;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Pantalla 12: detalle del módulo. RF-05, CU-04. */
class ModuloController extends Controller
{
    public function show(RutaModulo $rutaModulo, ServicioProgreso $progreso, CalificadorEvaluacion $calificador): View|RedirectResponse
    {
        $this->authorize('avanzar', $rutaModulo);

        if (! $progreso->accesible($rutaModulo)) {
            return redirect()->route('aspirante.ruta')->with('aviso', 'Para acceder a este módulo, concluya primero el módulo anterior.');
        }

        $rutaModulo->load('modulo.area', 'evaluaciones', 'valoracion');

        return view('aspirante.modulo', [
            'rutaModulo' => $rutaModulo,
            'recursos' => $rutaModulo->modulo->recursosActivos()->get(),
            'progresos' => ProgresoRecurso::where('id_ruta_modulo', $rutaModulo->id_ruta_modulo)->get()->keyBy('id_recurso'),
            'evaluacionHabilitada' => $progreso->evaluacionHabilitada($rutaModulo),
            'pendientes' => $progreso->recursosPendientes($rutaModulo),
            'maxIntentos' => $calificador->maxIntentos(),
            'umbral' => $calificador->umbral(),
        ]);
    }
}
