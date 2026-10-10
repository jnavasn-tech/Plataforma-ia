<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClavePrompt;
use App\Http\Controllers\Controller;
use App\Models\ConfiguracionPrompt;
use App\Services\Prompts\ServicioPrompts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Pantalla 24: configuración de prompts institucionales versionados (RF-08, CU-09 propuesto). */
class PromptController extends Controller
{
    public function __construct(private readonly ServicioPrompts $servicio)
    {
    }

    public function index(): View
    {
        return view('admin.prompts.index', [
            'grupos' => ConfiguracionPrompt::with('creador')->orderBy('clave')->orderByDesc('version')->get()->groupBy(fn ($p) => $p->clave->value),
            'claves' => ClavePrompt::cases(),
            'modelo' => config('ia.modelo'),
            'iaHabilitada' => config('ia.habilitada') && filled(config('ia.openai.api_key')),
        ]);
    }

    public function create(Request $request): View
    {
        $clave = ClavePrompt::tryFrom((string) $request->query('clave')) ?? ClavePrompt::Ruta;

        return view('admin.prompts.form', [
            'clave' => $clave,
            'claves' => ClavePrompt::cases(),
            'base' => ConfiguracionPrompt::activo($clave)?->contenido ?? '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'clave' => ['required', Rule::enum(ClavePrompt::class)],
            'contenido' => ['required', 'string', 'min:50', 'max:8000'],
            'descripcion_cambio' => ['required', 'string', 'max:255'],
            'activar' => ['nullable', 'boolean'],
        ]);

        $this->servicio->crearVersion(ClavePrompt::from($datos['clave']), $datos['contenido'], $datos['descripcion_cambio'], $request->user(), $request->boolean('activar'));

        return redirect()->route('admin.prompts.index')->with('estado', 'Se registró una nueva versión del prompt.');
    }

    public function activar(Request $request, ConfiguracionPrompt $prompt): RedirectResponse
    {
        $this->servicio->activar($prompt, $request->user());

        return back()->with('estado', "La versión {$prompt->version} del prompt quedó activa.");
    }
}
