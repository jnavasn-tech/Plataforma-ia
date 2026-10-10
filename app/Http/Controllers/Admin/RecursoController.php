<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoRecurso;
use App\Enums\TipoRecurso;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecursoRequest;
use App\Models\ModuloContenido;
use App\Models\RecursoAprendizaje;
use App\Services\Catalogo\ServicioRecursos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Pantalla 21: catálogo de recursos. CU-07 (alta, modificación y baja lógica). */
class RecursoController extends Controller
{
    public function __construct(private readonly ServicioRecursos $servicio)
    {
    }

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'id_modulo' => ['nullable', 'integer'],
            'estado' => ['nullable', Rule::enum(EstadoRecurso::class)],
            'tipo' => ['nullable', Rule::enum(TipoRecurso::class)],
        ]);

        return view('admin.recursos.index', [
            'recursos' => RecursoAprendizaje::with('modulo.area')
                ->when($filtros['id_modulo'] ?? null, fn ($q, $v) => $q->where('id_modulo', $v))
                ->when($filtros['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))
                ->when($filtros['tipo'] ?? null, fn ($q, $v) => $q->where('tipo', $v))
                ->orderBy('id_modulo')->orderBy('id_recurso')
                ->paginate(20)->withQueryString(),
            'modulos' => ModuloContenido::with('area')->orderBy('id_area')->orderBy('orden')->get(),
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return view('admin.recursos.form', $this->datosFormulario(new RecursoAprendizaje(['tipo' => TipoRecurso::Lectura])));
    }

    public function store(RecursoRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except(['guardar_pendiente', 'estado']);
        $accesible = empty($datos['url']) || $this->servicio->urlAccesible($datos['url']);

        if (! $accesible && ! $request->boolean('guardar_pendiente')) {
            return back()->withInput()
                ->withErrors(['url' => 'La URL no es accesible. Verifique el enlace antes de guardar.'])
                ->with('ofrecer_pendiente', true);
        }

        $recurso = $this->servicio->crear($datos, $request->user(), $accesible);
        $mensaje = $accesible ? 'Recurso registrado y disponible para nuevas rutas.' : 'Recurso guardado con estado "pendiente de verificación".';

        return redirect()->route('admin.recursos.index', ['id_modulo' => $recurso->id_modulo])->with('estado', $mensaje);
    }

    public function edit(RecursoAprendizaje $recurso): View
    {
        return view('admin.recursos.form', $this->datosFormulario($recurso));
    }

    public function update(RecursoRequest $request, RecursoAprendizaje $recurso): RedirectResponse
    {
        $datos = $request->safe()->except(['guardar_pendiente']);
        if (! empty($datos['url']) && $datos['url'] !== $recurso->url && ! $this->servicio->urlAccesible($datos['url'])) {
            if (! $request->boolean('guardar_pendiente')) {
                return back()->withInput()->withErrors(['url' => 'La URL no es accesible. Verifique el enlace antes de guardar.'])->with('ofrecer_pendiente', true);
            }
            $datos['estado'] = EstadoRecurso::PendienteVerificacion->value;
        }

        $this->servicio->actualizar($recurso, $datos, $request->user());

        return redirect()->route('admin.recursos.index', ['id_modulo' => $recurso->id_modulo])->with('estado', 'Recurso actualizado.');
    }

    public function cambiarEstado(Request $request, RecursoAprendizaje $recurso): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', Rule::enum(EstadoRecurso::class)]]);
        $this->servicio->actualizar($recurso, $datos, $request->user());

        return back()->with('estado', 'Estado del recurso actualizado.');
    }

    private function datosFormulario(RecursoAprendizaje $recurso): array
    {
        return [
            'recurso' => $recurso,
            'modulos' => ModuloContenido::with('area')->orderBy('id_area')->orderBy('orden')->get(),
            'tipos' => TipoRecurso::cases(),
            'estados' => EstadoRecurso::cases(),
        ];
    }
}
