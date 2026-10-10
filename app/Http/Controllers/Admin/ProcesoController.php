<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcesoSeleccion;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Procesos de selección (precondición de CU-05 y filtro de CU-06). */
class ProcesoController extends Controller
{
    public function __construct(private readonly RegistradorBitacora $bitacora)
    {
    }

    public function index(): View
    {
        return view('admin.procesos.index', ['procesos' => ProcesoSeleccion::withCount('postulaciones')->orderByDesc('fecha_inicio')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $proceso = ProcesoSeleccion::create($datos + ['activo' => $request->boolean('activo')]);
        $this->bitacora->registrar('proceso_creado', $request->user(), $proceso);

        return back()->with('estado', 'Proceso de selección registrado.');
    }

    public function update(Request $request, ProcesoSeleccion $proceso): RedirectResponse
    {
        $datos = $this->validar($request);
        $proceso->update($datos + ['activo' => $request->boolean('activo')]);
        $this->bitacora->registrar('proceso_actualizado', $request->user(), $proceso);

        return back()->with('estado', 'Proceso de selección actualizado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'puesto' => ['required', 'string', 'max:120'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ]);
    }
}
