<?php

namespace App\Http\Controllers\Admin;

use App\Dominio\Reportes\GraficoRadarSvg;
use App\Enums\EstadoPostulacion;
use App\Enums\NivelCompetencia;
use App\Enums\Rol;
use App\Enums\TipoDiagnostico;
use App\Http\Controllers\Controller;
use App\Models\PerfilCompetencia;
use App\Models\Postulacion;
use App\Models\ProcesoSeleccion;
use App\Models\Usuario;
use App\Services\Aspirantes\ServicioAspirantes;
use App\Services\Perfil\ServicioPerfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Pantallas 19 y 20: gestión y detalle de aspirantes. CU-05. */
class AspiranteController extends Controller
{
    public function __construct(private readonly ServicioAspirantes $servicio)
    {
    }

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'estado' => ['nullable', Rule::enum(EstadoPostulacion::class)],
            'id_proceso' => ['nullable', 'integer', 'exists:procesos_seleccion,id_proceso'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $aspirantes = Usuario::query()
            ->where('rol', Rol::Aspirante->value)
            ->with(['postulaciones.proceso', 'diagnosticos' => fn ($q) => $q->where('estado', 'completado')->with('perfil')])
            ->when($filtros['buscar'] ?? null, fn ($q, $texto) => $q->where('nombre', 'like', '%'.addcslashes($texto, '%_').'%'))
            ->when($filtros['desde'] ?? null, fn ($q, $fecha) => $q->whereDate('fecha_registro', '>=', $fecha))
            ->when($filtros['hasta'] ?? null, fn ($q, $fecha) => $q->whereDate('fecha_registro', '<=', $fecha))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->whereHas('postulaciones', fn ($p) => $p->where('estado', $estado)))
            ->when($filtros['id_proceso'] ?? null, fn ($q, $id) => $q->whereHas('postulaciones', fn ($p) => $p->where('id_proceso', $id)))
            ->orderByDesc('fecha_registro')
            ->paginate(15)
            ->withQueryString();

        return view('admin.aspirantes.index', [
            'aspirantes' => $aspirantes,
            'procesos' => ProcesoSeleccion::orderByDesc('fecha_inicio')->get(),
            'filtros' => $filtros,
        ]);
    }

    public function show(Usuario $aspirante, GraficoRadarSvg $radar): View
    {
        abort_unless($aspirante->esAspirante(), 404);

        $aspirante->load(['postulaciones.proceso', 'postulaciones.historial.administrador']);
        $pretest = $aspirante->ultimoDiagnosticoCompletado(TipoDiagnostico::Pretest->value);
        $postest = $aspirante->ultimoDiagnosticoCompletado(TipoDiagnostico::Postest->value);
        $perfil = $pretest?->perfil;
        $ruta = $perfil?->ruta?->load('modulos.modulo.area', 'modulos.evaluaciones');

        return view('admin.aspirantes.show', [
            'aspirante' => $aspirante,
            'perfil' => $perfil,
            'perfilPostest' => $postest?->perfil,
            'ruta' => $ruta,
            'grafico' => $perfil ? $radar->generar($perfil->puntajesPorNombre(), 'Perfil de competencias del aspirante', 320, $postest?->perfil?->puntajesPorNombre()) : null,
            'procesos' => ProcesoSeleccion::where('activo', true)->get(),
            'estados' => EstadoPostulacion::cases(),
            'niveles' => NivelCompetencia::cases(),
        ]);
    }

    public function actualizarEstado(Request $request, Usuario $aspirante, Postulacion $postulacion): RedirectResponse
    {
        abort_unless($postulacion->id_usuario === $aspirante->id_usuario, 404);
        $datos = $request->validate([
            'estado' => ['required', Rule::enum(EstadoPostulacion::class)],
            'comentario' => ['nullable', 'string', 'max:500'],
        ]);

        $this->servicio->cambiarEstado($postulacion, EstadoPostulacion::from($datos['estado']), $request->user(), $datos['comentario'] ?? null);

        return back()->with('estado', 'El estado del aspirante fue actualizado y quedó registrado en el historial.');
    }

    public function asignarProceso(Request $request, Usuario $aspirante): RedirectResponse
    {
        abort_unless($aspirante->esAspirante(), 404);
        $datos = $request->validate(['id_proceso' => ['required', 'integer', Rule::exists('procesos_seleccion', 'id_proceso')->where('activo', true)]]);

        $this->servicio->asignarProceso($aspirante, ProcesoSeleccion::findOrFail($datos['id_proceso']), $request->user());

        return back()->with('estado', 'El aspirante fue asignado al proceso de selección.');
    }

    public function enviarMensaje(Request $request, Usuario $aspirante): RedirectResponse
    {
        abort_unless($aspirante->esAspirante(), 404);
        $datos = $request->validate(['mensaje' => ['required', 'string', 'min:5', 'max:1000']]);

        $this->servicio->enviarMensaje($aspirante, $datos['mensaje'], $request->user());

        return back()->with('estado', 'Mensaje enviado al aspirante.');
    }

    public function reenviarInvitacion(Request $request, Usuario $aspirante): RedirectResponse
    {
        abort_unless($aspirante->esAspirante(), 404);
        if ($aspirante->ultimoDiagnosticoCompletado(TipoDiagnostico::Pretest->value)) {
            return back()->with('aviso', 'El aspirante ya completó el diagnóstico.');
        }

        $this->servicio->reenviarInvitacion($aspirante, $request->user());

        return back()->with('estado', 'Se reenvió la invitación al aspirante.');
    }

    public function registrarControl(Request $request, PerfilCompetencia $perfil, ServicioPerfil $servicioPerfil): RedirectResponse
    {
        $datos = $request->validate(['nivel_control' => ['required', Rule::enum(NivelCompetencia::class)]]);

        $servicioPerfil->registrarNivelControl($perfil, NivelCompetencia::from($datos['nivel_control']), $request->user());

        return back()->with('estado', 'Se registró el nivel de la evaluación de control.');
    }
}
