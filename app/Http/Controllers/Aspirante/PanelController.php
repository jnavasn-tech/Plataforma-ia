<?php

namespace App\Http\Controllers\Aspirante;

use App\Enums\EstadoRuta;
use App\Enums\TipoDiagnostico;
use App\Http\Controllers\Controller;
use App\Services\Diagnostico\ServicioDiagnostico;
use App\Services\Rutas\ServicioRutas;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pantalla 5: panel del aspirante (estado del diagnóstico, ruta y mensajes). */
class PanelController extends Controller
{
    public function index(Request $request, ServicioDiagnostico $diagnosticos, ServicioRutas $rutas): View
    {
        $usuario = $request->user();
        $pretest = $usuario->ultimoDiagnosticoCompletado(TipoDiagnostico::Pretest->value);
        $postest = $usuario->ultimoDiagnosticoCompletado(TipoDiagnostico::Postest->value);
        $perfil = $pretest?->perfil;
        $ruta = $perfil ? $rutas->asegurarRuta($perfil) : null;

        return view('aspirante.panel', [
            'usuario' => $usuario,
            'postulacion' => $usuario->postulacionActual()?->load('proceso'),
            'pretestActivo' => $diagnosticos->diagnosticoActivo($usuario, TipoDiagnostico::Pretest),
            'postestActivo' => $diagnosticos->diagnosticoActivo($usuario, TipoDiagnostico::Postest),
            'pretest' => $pretest,
            'postest' => $postest,
            'perfil' => $perfil,
            'ruta' => $ruta?->load('modulos'),
            'postestHabilitado' => $ruta?->estado === EstadoRuta::Completada && $postest === null,
            'mensajes' => $usuario->notifications()->latest()->limit(5)->get(),
        ]);
    }
}
