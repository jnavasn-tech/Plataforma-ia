<?php

namespace App\Services\Diagnostico;

use App\Dominio\Diagnostico\BancoInsuficienteException;
use App\Dominio\Diagnostico\CalculadoraPuntajes;
use App\Dominio\Diagnostico\ItemBanco;
use App\Dominio\Diagnostico\MotorAdaptativo;
use App\Dominio\Diagnostico\RespuestaRegistrada;
use App\Dominio\Perfil\ClasificadorNivel;
use App\Enums\EstadoDiagnostico;
use App\Enums\EstadoRuta;
use App\Enums\TipoDiagnostico;
use App\Enums\UsoPregunta;
use App\Jobs\ProcesarPerfilJob;
use App\Models\AreaCompetencia;
use App\Models\Diagnostico;
use App\Models\OpcionRespuesta;
use App\Models\PerfilCompetencia;
use App\Models\Pregunta;
use App\Models\RespuestaDiagnostico;
use App\Models\Usuario;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Caso de uso CU-02 "Realizar diagnóstico de competencias" (RF-02, RF-03).
 * Coordina el motor adaptativo, la persistencia de reactivos, el cálculo de puntajes y la
 * generación del perfil; al finalizar delega a la cola la interpretación y la ruta (CU-03).
 */
class ServicioDiagnostico
{
    /** @var Collection<int,AreaCompetencia>|null */
    private ?Collection $areas = null;

    public function __construct(
        private readonly MotorAdaptativo $motor,
        private readonly CalculadoraPuntajes $calculadora,
        private readonly ClasificadorNivel $clasificador,
        private readonly RegistradorBitacora $bitacora,
    ) {
    }

    /** @return Collection<int,AreaCompetencia> */
    public function areas(): Collection
    {
        return $this->areas ??= AreaCompetencia::activas()->get();
    }

    /** @return string[] */
    public function ordenAreas(): array
    {
        return $this->areas()->pluck('codigo')->all();
    }

    /** @return ItemBanco[] */
    public function bancoDominio(): array
    {
        $codigos = $this->areas()->pluck('codigo', 'id_area');

        return Pregunta::query()
            ->where('uso', UsoPregunta::Diagnostico->value)
            ->where('activo', true)
            ->whereIn('id_area', $codigos->keys())
            ->get(['id_pregunta', 'id_area', 'dificultad'])
            ->map(fn (Pregunta $p) => new ItemBanco($p->id_pregunta, $codigos[$p->id_area], $p->dificultad))
            ->all();
    }

    public function diagnosticoActivo(Usuario $usuario, TipoDiagnostico $tipo): ?Diagnostico
    {
        return $usuario->diagnosticos()
            ->where('tipo', $tipo->value)
            ->whereIn('estado', [EstadoDiagnostico::Iniciado->value, EstadoDiagnostico::EnProgreso->value])
            ->latest('id_diagnostico')
            ->first();
    }

    /** Devuelve null si el aspirante puede iniciar el diagnóstico o el motivo por el que no puede. */
    public function motivoNoElegible(Usuario $usuario, TipoDiagnostico $tipo): ?string
    {
        $idProceso = $usuario->postulacionActual()?->id_proceso;
        $completado = $usuario->diagnosticos()
            ->where('tipo', $tipo->value)
            ->where('estado', EstadoDiagnostico::Completado->value)
            ->when($idProceso, fn ($q) => $q->where('id_proceso', $idProceso))
            ->exists();

        if ($completado) {
            return $tipo === TipoDiagnostico::Pretest
                ? 'Usted ya completó el diagnóstico inicial en el proceso de selección vigente.'
                : 'Usted ya completó el diagnóstico final en el proceso de selección vigente.';
        }

        if ($tipo === TipoDiagnostico::Postest) {
            $ruta = $usuario->ultimoDiagnosticoCompletado(TipoDiagnostico::Pretest->value)?->perfil?->ruta;
            if ($ruta === null || $ruta->estado !== EstadoRuta::Completada) {
                return 'El diagnóstico final se habilita al completar todos los módulos de su ruta de aprendizaje.';
            }
        }

        return null;
    }

    /**
     * Inicia un diagnóstico o reanuda el que está en progreso (flujo alterno de sesión expirada).
     *
     * @throws DiagnosticoNoPermitidoException|BancoInsuficienteException
     */
    public function iniciarOReanudar(Usuario $usuario, TipoDiagnostico $tipo): Diagnostico
    {
        return DB::transaction(function () use ($usuario, $tipo) {
            $activo = $this->diagnosticoActivo($usuario, $tipo);
            if ($activo !== null) {
                if (! $this->excedeInactividad($activo)) {
                    return $activo;
                }
                $activo->update(['estado' => EstadoDiagnostico::Abandonado]);
                $this->bitacora->registrar('diagnostico_abandonado', $usuario, $activo);
            }

            if ($motivo = $this->motivoNoElegible($usuario, $tipo)) {
                throw new DiagnosticoNoPermitidoException($motivo);
            }

            $faltantes = $this->motor->areasInsuficientes($this->ordenAreas(), $this->bancoDominio());
            if ($faltantes !== [] || $this->ordenAreas() === []) {
                throw new BancoInsuficienteException($faltantes);
            }

            $diagnostico = Diagnostico::create([
                'id_usuario' => $usuario->id_usuario,
                'id_proceso' => $usuario->postulacionActual()?->id_proceso,
                'tipo' => $tipo,
                'estado' => EstadoDiagnostico::Iniciado,
                'total_items' => $this->motor->totalItems($this->ordenAreas()),
                'iniciado_en' => now(),
                'ultima_actividad_en' => now(),
            ]);
            $this->bitacora->registrar('diagnostico_iniciado', $usuario, $diagnostico, ['tipo' => $tipo->value]);

            return $diagnostico;
        });
    }

    /** Ítem pendiente de respuesta; si no existe, selecciona y registra el siguiente. */
    public function itemActual(Diagnostico $diagnostico): ?RespuestaDiagnostico
    {
        if (! $diagnostico->estado->esActivo()) {
            return null;
        }

        $pendiente = $diagnostico->respuestas()->whereNull('respondida_en')->with('pregunta.opciones', 'pregunta.area')->first();
        if ($pendiente !== null) {
            return $pendiente;
        }

        $presentados = $diagnostico->respuestas()->pluck('id_pregunta')->all();
        $item = $this->motor->siguienteItem($this->ordenAreas(), $this->bancoDominio(), $this->historial($diagnostico), $presentados);
        if ($item === null) {
            return null;
        }

        return RespuestaDiagnostico::create([
            'id_diagnostico' => $diagnostico->id_diagnostico,
            'id_pregunta' => $item->idPregunta,
            'orden' => count($presentados) + 1,
            'dificultad' => $item->dificultad,
            'presentada_en' => now(),
        ])->load('pregunta.opciones', 'pregunta.area');
    }

    /**
     * Registra la respuesta del reactivo pendiente. Devuelve true cuando el diagnóstico queda completado.
     *
     * @throws ValidationException|DiagnosticoNoPermitidoException
     */
    public function responder(Diagnostico $diagnostico, int $idRespuesta, int $idOpcion): bool
    {
        $inicio = hrtime(true);

        $perfil = DB::transaction(function () use ($diagnostico, $idRespuesta, $idOpcion, $inicio) {
            $diagnostico = Diagnostico::whereKey($diagnostico->id_diagnostico)->lockForUpdate()->firstOrFail();
            if (! $diagnostico->estado->esActivo()) {
                throw new DiagnosticoNoPermitidoException('El diagnóstico ya no está activo.');
            }

            $item = $diagnostico->respuestas()->whereKey($idRespuesta)->whereNull('respondida_en')->first();
            if ($item === null) {
                // Doble envío del mismo formulario: se ignora sin alterar el estado.
                return null;
            }

            $opcion = OpcionRespuesta::where('id_pregunta', $item->id_pregunta)->whereKey($idOpcion)->first();
            if ($opcion === null) {
                throw ValidationException::withMessages(['id_opcion' => 'Por favor seleccione una respuesta antes de continuar.']);
            }

            $ahora = now();
            $item->fill([
                'id_opcion' => $opcion->id_opcion,
                'es_correcta' => $opcion->es_correcta,
                'respondida_en' => $ahora,
                'tiempo_respuesta_ms' => $item->presentada_en ? (int) $item->presentada_en->diffInMilliseconds($ahora) : null,
            ])->save();

            $diagnostico->update(['estado' => EstadoDiagnostico::EnProgreso, 'ultima_actividad_en' => $ahora]);

            $perfil = $diagnostico->respondidas() >= $diagnostico->total_items
                ? $this->finalizar($diagnostico)
                : null;

            if ($perfil === null) {
                $this->itemActual($diagnostico);
            }

            $item->update(['tiempo_servidor_ms' => (int) round((hrtime(true) - $inicio) / 1_000_000)]);

            return $perfil;
        });

        if ($perfil instanceof PerfilCompetencia) {
            ProcesarPerfilJob::dispatch($perfil->id_perfil);

            return true;
        }

        return false;
    }

    /** Calcula puntajes por área, clasifica el nivel y persiste el perfil (RF-03). */
    public function finalizar(Diagnostico $diagnostico): PerfilCompetencia
    {
        $historial = $this->historial($diagnostico);
        $orden = $this->ordenAreas();
        $puntajes = $this->calculadora->puntajesPorArea($historial, $orden);
        $global = $this->calculadora->puntajeGlobal($puntajes);
        $nivel = $this->clasificador->clasificar($global);
        $nombres = $this->areas()->pluck('nombre', 'codigo');

        $areasJson = [];
        foreach ($puntajes as $codigo => $puntaje) {
            $areasJson[] = [
                'codigo' => $codigo,
                'nombre' => $nombres[$codigo],
                'puntaje' => $puntaje,
                'nivel' => $this->clasificador->clasificar($puntaje)->value,
                'respondidas' => count(array_filter($historial, fn (RespuestaRegistrada $r) => $r->codigoArea === $codigo)),
            ];
        }

        $perfil = PerfilCompetencia::create([
            'id_diagnostico' => $diagnostico->id_diagnostico,
            'nivel_general' => $nivel,
            'puntaje_global' => $global,
            'areas_evaluadas_json' => $areasJson,
        ]);

        $diagnostico->update(['estado' => EstadoDiagnostico::Completado, 'finalizado_en' => now()]);
        $this->bitacora->registrar('diagnostico_completado', $diagnostico->usuario, $diagnostico, [
            'tipo' => $diagnostico->tipo->value,
            'puntaje_global' => $global,
            'nivel' => $nivel->value,
        ]);

        return $perfil;
    }

    /** @return RespuestaRegistrada[] */
    public function historial(Diagnostico $diagnostico): array
    {
        $codigos = AreaCompetencia::pluck('codigo', 'id_area');

        return $diagnostico->respuestas()
            ->whereNotNull('respondida_en')
            ->with('pregunta:id_pregunta,id_area')
            ->get()
            ->map(fn (RespuestaDiagnostico $r) => new RespuestaRegistrada(
                $r->id_pregunta,
                $codigos[$r->pregunta->id_area],
                $r->dificultad,
                (bool) $r->es_correcta
            ))
            ->all();
    }

    /** @return array{respondidas:int,total:int,porcentaje:float,area_actual:?string,indice_area:int,total_areas:int} */
    public function progreso(Diagnostico $diagnostico, ?RespuestaDiagnostico $actual = null): array
    {
        $respondidas = $diagnostico->respondidas();
        $total = max(1, $diagnostico->total_items);
        $areas = $this->ordenAreas();
        $codigoActual = $actual?->pregunta?->area?->codigo;
        $indice = $codigoActual ? array_search($codigoActual, $areas, true) + 1 : min(count($areas), intdiv($respondidas, $this->motor->itemsPorArea()) + 1);

        return [
            'respondidas' => $respondidas,
            'total' => $diagnostico->total_items,
            'porcentaje' => round($respondidas / $total * 100, 2),
            'area_actual' => $actual?->pregunta?->area?->nombre,
            'indice_area' => (int) $indice,
            'total_areas' => count($areas),
        ];
    }

    private function excedeInactividad(Diagnostico $diagnostico): bool
    {
        $horas = (int) config('plataforma.diagnostico.horas_inactividad_abandono', 72);
        $referencia = $diagnostico->ultima_actividad_en ?? $diagnostico->iniciado_en;

        return $referencia !== null && $referencia->lt(now()->subHours($horas));
    }
}
