<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\BitacoraIA;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pantalla 25: bitácora de actividad y bitácora técnica de IA (RF-08). */
class BitacoraController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'vista' => ['nullable', 'in:actividad,ia'],
            'accion' => ['nullable', 'string', 'max:60'],
            'tipo' => ['nullable', 'in:diagnostico,ruta,retroalimentacion'],
            'estado' => ['nullable', 'in:exito,fallback,deshabilitada'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);
        $vista = $filtros['vista'] ?? 'actividad';

        $registros = $vista === 'ia'
            ? BitacoraIA::query()
                ->when($filtros['tipo'] ?? null, fn ($q, $v) => $q->where('tipo', $v))
                ->when($filtros['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))
                ->when($filtros['desde'] ?? null, fn ($q, $v) => $q->whereDate('fecha', '>=', $v))
                ->when($filtros['hasta'] ?? null, fn ($q, $v) => $q->whereDate('fecha', '<=', $v))
                ->orderByDesc('id_bitacora_ia')->paginate(25)->withQueryString()
            : Bitacora::with('usuario:id_usuario,nombre,rol')
                ->when($filtros['accion'] ?? null, fn ($q, $v) => $q->where('accion', $v))
                ->when($filtros['desde'] ?? null, fn ($q, $v) => $q->whereDate('fecha', '>=', $v))
                ->when($filtros['hasta'] ?? null, fn ($q, $v) => $q->whereDate('fecha', '<=', $v))
                ->orderByDesc('id_bitacora')->paginate(25)->withQueryString();

        return view('admin.bitacora.index', [
            'vista' => $vista,
            'registros' => $registros,
            'acciones' => Bitacora::query()->distinct()->orderBy('accion')->pluck('accion'),
            'filtros' => $filtros,
        ]);
    }
}
