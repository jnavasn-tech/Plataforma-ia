<?php

namespace App\Http\Controllers\Aspirante;

use App\Dominio\Reportes\GraficoRadarSvg;
use App\Enums\TipoDiagnostico;
use App\Http\Controllers\Controller;
use App\Services\Rutas\ServicioProgreso;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pantalla 16: progreso general (ruta, evaluaciones, puntaje formativo y comparación pretest-postest). */
class ProgresoController extends Controller
{
    public function index(Request $request, ServicioProgreso $progreso, GraficoRadarSvg $radar): View
    {
        $usuario = $request->user();
        $pretest = $usuario->ultimoDiagnosticoCompletado(TipoDiagnostico::Pretest->value)?->perfil;
        $postest = $usuario->ultimoDiagnosticoCompletado(TipoDiagnostico::Postest->value)?->perfil;
        $ruta = $pretest?->ruta;

        return view('aspirante.progreso', [
            'pretest' => $pretest,
            'postest' => $postest,
            'ruta' => $ruta,
            'modulos' => $ruta?->modulos()->with(['modulo.area', 'evaluaciones'])->get() ?? collect(),
            'formativos' => $ruta ? $progreso->puntajesFormativos($ruta) : [],
            'avance' => $ruta?->porcentajeAvance() ?? 0.0,
            'grafico' => $pretest ? $radar->generar($pretest->puntajesPorNombre(), 'Comparación de resultados', 340, $postest?->puntajesPorNombre()) : null,
        ]);
    }
}
