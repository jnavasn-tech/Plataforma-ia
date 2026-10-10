<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NivelCompetencia;
use App\Http\Controllers\Controller;
use App\Models\AreaCompetencia;
use App\Models\ModuloContenido;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Gestión de módulos formativos (RF-05: "el administrador podrá crear/editar módulos"). */
class ModuloController extends Controller
{
    public function __construct(private readonly RegistradorBitacora $bitacora)
    {
    }

    public function index(): View
    {
        return view('admin.modulos.index', [
            'modulos' => ModuloContenido::with('area')
                ->withCount(['recursos as recursos_activos' => fn ($q) => $q->where('estado', 'activo'), 'preguntasEvaluacion'])
                ->orderBy('id_area')->orderBy('orden')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.modulos.form', ['modulo' => new ModuloContenido(['activo' => true, 'orden' => 1]), 'areas' => AreaCompetencia::activas()->get(), 'niveles' => NivelCompetencia::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $modulo = ModuloContenido::create($this->validar($request) + ['activo' => $request->boolean('activo')]);
        $this->bitacora->registrar('modulo_creado', $request->user(), $modulo);

        return redirect()->route('admin.modulos.index')->with('estado', 'Módulo registrado.');
    }

    public function edit(ModuloContenido $modulo): View
    {
        return view('admin.modulos.form', ['modulo' => $modulo, 'areas' => AreaCompetencia::activas()->get(), 'niveles' => NivelCompetencia::cases()]);
    }

    public function update(Request $request, ModuloContenido $modulo): RedirectResponse
    {
        $modulo->update($this->validar($request, $modulo) + ['activo' => $request->boolean('activo')]);
        $this->bitacora->registrar('modulo_actualizado', $request->user(), $modulo);

        return redirect()->route('admin.modulos.index')->with('estado', 'Módulo actualizado.');
    }

    private function validar(Request $request, ?ModuloContenido $modulo = null): array
    {
        return $request->validate([
            'id_area' => ['required', 'integer', 'exists:areas_competencia,id_area'],
            'codigo' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('modulos_contenido', 'codigo')->ignore($modulo?->id_modulo, 'id_modulo')],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'nivel' => ['required', Rule::enum(NivelCompetencia::class)],
            'orden' => ['required', 'integer', 'between:1,99'],
        ]);
    }
}
