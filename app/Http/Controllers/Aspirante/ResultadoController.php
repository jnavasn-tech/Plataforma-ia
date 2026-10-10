<?php

namespace App\Http\Controllers\Aspirante;

use App\Dominio\Reportes\GraficoRadarSvg;
use App\Enums\EstadoDiagnostico;
use App\Enums\TipoDiagnostico;
use App\Http\Controllers\Controller;
use App\Models\Diagnostico;
use App\Services\Rutas\ServicioRutas;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Pantallas 9 y 10: resultados, perfil de competencias y gráfico de radar. RF-03, CU-02. */
class ResultadoController extends Controller
{
    public function show(Diagnostico $diagnostico, ServicioRutas $rutas, GraficoRadarSvg $radar): View|RedirectResponse
    {
        $this->authorize('ver', $diagnostico);

        $perfil = $diagnostico->perfil;
        if ($diagnostico->estado !== EstadoDiagnostico::Completado || $perfil === null) {
            return redirect()->route('aspirante.diagnostico.mostrar', $diagnostico);
        }

        $comparacion = null;
        if ($diagnostico->tipo === TipoDiagnostico::Postest) {
            $comparacion = $diagnostico->usuario->ultimoDiagnosticoCompletado(TipoDiagnostico::Pretest->value)?->perfil;
        }
        $ruta = $diagnostico->tipo === TipoDiagnostico::Pretest ? $rutas->asegurarRuta($perfil) : null;

        return view('aspirante.resultados', [
            'diagnostico' => $diagnostico,
            'perfil' => $perfil,
            'ruta' => $ruta,
            'comparacion' => $comparacion,
            'grafico' => $radar->generar(
                $perfil->puntajesPorNombre(),
                'Gráfico de radar del perfil de competencias',
                360,
                $comparacion?->puntajesPorNombre()
            ),
            'refrescar' => $diagnostico->tipo === TipoDiagnostico::Pretest && $ruta === null,
        ]);
    }
}
