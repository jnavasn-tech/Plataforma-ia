<?php

namespace App\Http\Controllers\Aspirante;

use App\Enums\TipoDiagnostico;
use App\Http\Controllers\Controller;
use App\Services\Rutas\ServicioProgreso;
use App\Services\Rutas\ServicioRutas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pantalla 11: ruta de aprendizaje. RF-04, CU-03 (resultado) y CU-04 (paso 2). */
class RutaController extends Controller
{
    public function show(Request $request, ServicioRutas $rutas, ServicioProgreso $progreso): View|RedirectResponse
    {
        $perfil = $request->user()->ultimoDiagnosticoCompletado(TipoDiagnostico::Pretest->value)?->perfil;
        if ($perfil === null) {
            return redirect()->route('aspirante.panel')->with('aviso', 'Complete primero el diagnóstico de competencias.');
        }

        $ruta = $rutas->asegurarRuta($perfil);
        if ($ruta === null) {
            return redirect()->route('aspirante.resultados', $perfil->id_diagnostico)->with('aviso', 'Su ruta se está generando. Espere unos segundos.');
        }
        $this->authorize('ver', $ruta);

        $modulos = $ruta->modulos()->with('modulo.area')->get();

        return view('aspirante.ruta', [
            'ruta' => $ruta,
            'modulos' => $modulos,
            'accesibles' => $modulos->mapWithKeys(fn ($m) => [$m->id_ruta_modulo => $progreso->accesible($m)]),
            'avance' => $ruta->porcentajeAvance(),
        ]);
    }
}
