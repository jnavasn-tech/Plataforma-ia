<?php

namespace App\Http\Controllers\Aspirante;

use App\Http\Controllers\Controller;
use App\Models\RecursoAprendizaje;
use App\Models\RutaModulo;
use App\Services\Rutas\ServicioProgreso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pantalla 13: recurso formativo. CU-04 (pasos 3 a 6 y flujo alterno de recurso no disponible). */
class RecursoController extends Controller
{
    public function __construct(private readonly ServicioProgreso $progreso)
    {
    }

    public function show(Request $request, RutaModulo $rutaModulo, RecursoAprendizaje $recurso): View|RedirectResponse
    {
        $this->authorize('avanzar', $rutaModulo);
        if (! $this->progreso->accesible($rutaModulo)) {
            return redirect()->route('aspirante.ruta')->with('aviso', 'Para acceder a este módulo, concluya primero el módulo anterior.');
        }

        $registro = $this->progreso->iniciarRecurso($request->user(), $rutaModulo, $recurso);
        $alternativas = RecursoAprendizaje::whereIn('id_recurso', (array) session('alternativas', []))->get();

        return view('aspirante.recurso', [
            'rutaModulo' => $rutaModulo->load('modulo'),
            'recurso' => $recurso,
            'registro' => $registro,
            'alternativas' => $alternativas,
        ]);
    }

    public function completar(Request $request, RutaModulo $rutaModulo, RecursoAprendizaje $recurso): RedirectResponse
    {
        $this->authorize('avanzar', $rutaModulo);
        $this->progreso->completarRecurso($request->user(), $rutaModulo, $recurso);

        return redirect()->route('aspirante.modulo', $rutaModulo)->with('estado', 'Recurso marcado como completado.');
    }

    public function reportar(Request $request, RutaModulo $rutaModulo, RecursoAprendizaje $recurso): RedirectResponse
    {
        $this->authorize('avanzar', $rutaModulo);
        $alternativas = $this->progreso->reportarNoDisponible($request->user(), $rutaModulo, $recurso);

        return redirect()->route('aspirante.recurso', [$rutaModulo, $recurso])
            ->with('aviso', 'Este recurso no está disponible temporalmente. Le sugerimos estos recursos alternativos:')
            ->with('alternativas', $alternativas->pluck('id_recurso')->all());
    }
}
