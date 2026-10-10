<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UsoPregunta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreguntaRequest;
use App\Models\AreaCompetencia;
use App\Models\Evaluacion;
use App\Models\ModuloContenido;
use App\Models\Pregunta;
use App\Models\RespuestaDiagnostico;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pantalla 22: gestión del banco de preguntas.
 * Una pregunta ya utilizada no se modifica: se crea una nueva versión y la anterior se desactiva,
 * para conservar la integridad de las respuestas históricas.
 */
class PreguntaController extends Controller
{
    public function __construct(private readonly RegistradorBitacora $bitacora)
    {
    }

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'uso' => ['nullable', Rule::enum(UsoPregunta::class)],
            'id_area' => ['nullable', 'integer'],
            'dificultad' => ['nullable', 'integer', 'between:1,3'],
        ]);

        return view('admin.preguntas.index', [
            'preguntas' => Pregunta::with('area', 'modulo')->withCount('opciones')
                ->when($filtros['uso'] ?? null, fn ($q, $v) => $q->where('uso', $v))
                ->when($filtros['id_area'] ?? null, fn ($q, $v) => $q->where('id_area', $v))
                ->when($filtros['dificultad'] ?? null, fn ($q, $v) => $q->where('dificultad', $v))
                ->orderBy('id_area')->orderBy('uso')->orderBy('dificultad')
                ->paginate(20)->withQueryString(),
            'areas' => AreaCompetencia::activas()->get(),
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return view('admin.preguntas.form', $this->datosFormulario(new Pregunta(['uso' => UsoPregunta::Diagnostico, 'dificultad' => 2, 'activo' => true])));
    }

    public function store(PreguntaRequest $request): RedirectResponse
    {
        $pregunta = $this->guardar($request->validated(), $request);
        $this->bitacora->registrar('pregunta_creada', $request->user(), $pregunta);

        return redirect()->route('admin.preguntas.index')->with('estado', 'Pregunta registrada en el banco.');
    }

    public function edit(Pregunta $pregunta): View
    {
        return view('admin.preguntas.form', $this->datosFormulario($pregunta->load('opciones')));
    }

    public function update(PreguntaRequest $request, Pregunta $pregunta): RedirectResponse
    {
        if ($this->fueUtilizada($pregunta)) {
            $nueva = DB::transaction(function () use ($request, $pregunta) {
                $pregunta->update(['activo' => false]);

                return $this->guardar($request->validated(), $request, $pregunta->version + 1);
            });
            $this->bitacora->registrar('pregunta_versionada', $request->user(), $nueva, ['id_pregunta_anterior' => $pregunta->id_pregunta]);

            return redirect()->route('admin.preguntas.index')->with('estado', 'La pregunta ya había sido utilizada: se creó una nueva versión y la anterior quedó inactiva.');
        }

        DB::transaction(function () use ($request, $pregunta) {
            $datos = $request->validated();
            $pregunta->update($this->atributos($datos));
            $pregunta->opciones()->delete();
            $this->crearOpciones($pregunta, $datos);
        });
        $this->bitacora->registrar('pregunta_actualizada', $request->user(), $pregunta);

        return redirect()->route('admin.preguntas.index')->with('estado', 'Pregunta actualizada.');
    }

    public function cambiarEstado(Request $request, Pregunta $pregunta): RedirectResponse
    {
        $pregunta->update(['activo' => ! $pregunta->activo]);
        $this->bitacora->registrar($pregunta->activo ? 'pregunta_activada' : 'pregunta_desactivada', $request->user(), $pregunta);

        return back()->with('estado', $pregunta->activo ? 'Pregunta activada.' : 'Pregunta desactivada.');
    }

    private function guardar(array $datos, Request $request, int $version = 1): Pregunta
    {
        return DB::transaction(function () use ($datos, $request, $version) {
            $pregunta = Pregunta::create($this->atributos($datos) + ['activo' => true, 'version' => $version, 'creado_por' => $request->user()->id_usuario]);
            $this->crearOpciones($pregunta, $datos);

            return $pregunta;
        });
    }

    private function atributos(array $datos): array
    {
        return [
            'id_area' => $datos['id_area'],
            'id_modulo' => $datos['uso'] === UsoPregunta::EvaluacionModulo->value ? $datos['id_modulo'] : null,
            'uso' => $datos['uso'],
            'dificultad' => $datos['dificultad'],
            'enunciado' => $datos['enunciado'],
            'explicacion' => $datos['explicacion'] ?? null,
        ];
    }

    private function crearOpciones(Pregunta $pregunta, array $datos): void
    {
        foreach (array_values($datos['opciones']) as $i => $texto) {
            $pregunta->opciones()->create(['texto' => $texto, 'es_correcta' => $i === (int) $datos['correcta'], 'orden' => $i + 1]);
        }
    }

    private function fueUtilizada(Pregunta $pregunta): bool
    {
        return RespuestaDiagnostico::where('id_pregunta', $pregunta->id_pregunta)->exists()
            || ($pregunta->id_modulo && Evaluacion::where('id_modulo', $pregunta->id_modulo)->exists());
    }

    private function datosFormulario(Pregunta $pregunta): array
    {
        return [
            'pregunta' => $pregunta,
            'areas' => AreaCompetencia::activas()->get(),
            'modulos' => ModuloContenido::with('area')->orderBy('id_area')->orderBy('orden')->get(),
            'usos' => UsoPregunta::cases(),
        ];
    }
}
