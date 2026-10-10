<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UsoPregunta;
use App\Http\Controllers\Controller;
use App\Models\AreaCompetencia;
use App\Models\Pregunta;
use App\Services\Metricas\ServicioMetricas;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pantalla 18: panel administrativo con métricas de uso, indicadores y alertas (RF-08). */
class PanelController extends Controller
{
    public function index(Request $request, ServicioMetricas $metricas): View
    {
        $minimo = (int) config('plataforma.diagnostico.items_por_area');
        $banco = AreaCompetencia::activas()->get()->map(fn (AreaCompetencia $area) => [
            'area' => $area->nombre,
            'disponibles' => Pregunta::where('id_area', $area->id_area)->where('uso', UsoPregunta::Diagnostico->value)->where('activo', true)->count(),
            'minimo' => $minimo,
        ]);

        return view('admin.panel', [
            'metricas' => $metricas->resumen(),
            'banco' => $banco,
            'notificaciones' => $request->user()->unreadNotifications()->limit(10)->get(),
        ]);
    }
}
