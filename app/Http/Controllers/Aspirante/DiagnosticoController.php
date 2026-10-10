<?php

namespace App\Http\Controllers\Aspirante;

use App\Enums\EstadoDiagnostico;
use App\Enums\TipoDiagnostico;
use App\Http\Controllers\Controller;
use App\Models\Diagnostico;
use App\Services\Diagnostico\ServicioDiagnostico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pantallas 6, 7 y 8: instrucciones, pregunta y progreso del diagnóstico. RF-02, CU-02. */
class DiagnosticoController extends Controller
{
    public function __construct(private readonly ServicioDiagnostico $servicio)
    {
    }

    public function instrucciones(Request $request, string $tipo): View
    {
        $tipoDiagnostico = TipoDiagnostico::from($tipo);
        $usuario = $request->user();
        $activo = $this->servicio->diagnosticoActivo($usuario, $tipoDiagnostico);

        return view('aspirante.diagnostico.instrucciones', [
            'tipo' => $tipoDiagnostico,
            'areas' => $this->servicio->areas(),
            'itemsPorArea' => (int) config('plataforma.diagnostico.items_por_area'),
            'activo' => $activo,
            'motivo' => $activo ? null : $this->servicio->motivoNoElegible($usuario, $tipoDiagnostico),
        ]);
    }

    public function iniciar(Request $request, string $tipo): RedirectResponse
    {
        $diagnostico = $this->servicio->iniciarOReanudar($request->user(), TipoDiagnostico::from($tipo));

        return redirect()->route('aspirante.diagnostico.mostrar', $diagnostico);
    }

    public function mostrar(Diagnostico $diagnostico): View|RedirectResponse
    {
        $this->authorize('responder', $diagnostico);

        if ($diagnostico->estado === EstadoDiagnostico::Completado) {
            return redirect()->route('aspirante.resultados', $diagnostico);
        }
        if ($diagnostico->estado === EstadoDiagnostico::Abandonado) {
            return redirect()->route('aspirante.panel')->with('aviso', 'El diagnóstico se cerró por inactividad. Puede iniciar uno nuevo.');
        }

        $item = $this->servicio->itemActual($diagnostico);
        if ($item === null) {
            return redirect()->route('aspirante.resultados', $diagnostico);
        }

        return view('aspirante.diagnostico.pregunta', [
            'diagnostico' => $diagnostico,
            'item' => $item,
            'progreso' => $this->servicio->progreso($diagnostico, $item),
        ]);
    }

    public function responder(Request $request, Diagnostico $diagnostico): RedirectResponse
    {
        $this->authorize('responder', $diagnostico);

        $datos = $request->validate([
            'id_respuesta' => ['required', 'integer'],
            'id_opcion' => ['required', 'integer'],
        ], [
            'id_opcion.required' => 'Por favor seleccione una respuesta antes de continuar.',
        ]);

        $completado = $this->servicio->responder($diagnostico, (int) $datos['id_respuesta'], (int) $datos['id_opcion']);

        return $completado
            ? redirect()->route('aspirante.resultados', $diagnostico)->with('estado', 'Diagnóstico completado. Estos son sus resultados.')
            : redirect()->route('aspirante.diagnostico.mostrar', $diagnostico);
    }
}
