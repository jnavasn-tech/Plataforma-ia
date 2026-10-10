<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\ProcesoSeleccion;
use App\Models\Usuario;
use App\Services\Reportes\ServicioReportes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Pantalla 23: reportes de competencias en PDF y Excel. CU-06. */
class ReporteController extends Controller
{
    private const SIN_DATOS = 'No hay datos disponibles para el período seleccionado. Ajuste el rango de fechas o verifique el proceso de selección.';

    public function __construct(private readonly ServicioReportes $servicio)
    {
    }

    public function index(Request $request): View
    {
        $filtros = $this->validar($request, false);
        $filas = $request->has('consultar')
            ? $this->servicio->comparativo($filtros['id_proceso'] ?? null, $filtros['desde'] ?? null, $filtros['hasta'] ?? null, $filtros['id_usuario'] ?? null)
            : null;

        return view('admin.reportes.index', [
            'procesos' => ProcesoSeleccion::orderByDesc('fecha_inicio')->get(),
            'aspirantes' => Usuario::where('rol', Rol::Aspirante->value)->orderBy('nombre')->get(['id_usuario', 'nombre']),
            'filtros' => $filtros,
            'filas' => $filas,
            'graficos' => $filas ? $this->servicio->graficos($filas->take(10)) : [],
            'sinDatos' => $filas !== null && $filas->isEmpty() ? self::SIN_DATOS : null,
        ]);
    }

    public function exportar(Request $request): Response|RedirectResponse
    {
        $filtros = $this->validar($request, true);
        $filas = $this->servicio->comparativo($filtros['id_proceso'] ?? null, $filtros['desde'] ?? null, $filtros['hasta'] ?? null, $filtros['id_usuario'] ?? null);

        if ($filas->isEmpty()) {
            return redirect()->route('admin.reportes.index', $request->except('formato'))->with('aviso', self::SIN_DATOS);
        }

        $resumenFiltros = array_filter([
            'tipo' => $filtros['tipo'] ?? 'comparativo',
            'id_proceso' => $filtros['id_proceso'] ?? null,
            'desde' => $filtros['desde'] ?? null,
            'hasta' => $filtros['hasta'] ?? null,
        ]);

        return $filtros['formato'] === 'pdf'
            ? $this->servicio->exportarPdf($filas, $resumenFiltros, $request->user())
            : $this->servicio->exportarExcel($filas, $resumenFiltros, $request->user());
    }

    private function validar(Request $request, bool $exportar): array
    {
        return $request->validate([
            'tipo' => ['nullable', 'in:comparativo,individual'],
            'id_proceso' => ['nullable', 'integer', 'exists:procesos_seleccion,id_proceso'],
            'id_usuario' => ['nullable', 'required_if:tipo,individual', 'integer', 'exists:usuarios,id_usuario'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'formato' => [$exportar ? 'required' : 'nullable', 'in:pdf,xlsx'],
        ]);
    }
}
