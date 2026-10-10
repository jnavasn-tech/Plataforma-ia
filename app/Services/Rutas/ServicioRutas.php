<?php

namespace App\Services\Rutas;

use App\Dominio\IA\MinimizadorDatos;
use App\Dominio\IA\ValidadorRespuestaIA;
use App\Dominio\Rutas\GeneradorRutaReglas;
use App\Dominio\Rutas\ModuloCatalogo;
use App\Dominio\Rutas\PlanRuta;
use App\Enums\ClavePrompt;
use App\Enums\EstadoRecurso;
use App\Enums\EstadoRuta;
use App\Enums\EstadoSolicitudIA;
use App\Enums\OrigenGeneracion;
use App\Enums\Rol;
use App\Models\BitacoraIA;
use App\Models\ModuloContenido;
use App\Models\PerfilCompetencia;
use App\Models\RutaAprendizaje;
use App\Models\RutaModulo;
use App\Models\Usuario;
use App\Notifications\PerfilMaximoNotificacion;
use App\Services\Bitacora\RegistradorBitacora;
use App\Services\IA\ResultadoIA;
use App\Services\IA\ServicioIA;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Caso de uso CU-03 "Generar ruta de aprendizaje con IA" (RF-04).
 * La IA propone la selección y el orden de módulos del catálogo; la propuesta se valida y, si no cumple,
 * se usa la ruta generada por reglas. En ambos casos la ruta queda trazada con su origen y versión de prompt.
 */
class ServicioRutas
{
    public function __construct(
        private readonly ServicioIA $ia,
        private readonly GeneradorRutaReglas $generador,
        private readonly ValidadorRespuestaIA $validador,
        private readonly MinimizadorDatos $minimizador,
        private readonly RegistradorBitacora $bitacora,
    ) {
    }

    public function generarParaPerfil(PerfilCompetencia $perfil, bool $soloReglas = false): RutaAprendizaje
    {
        if ($existente = $perfil->ruta()->first()) {
            return $existente;
        }

        $puntajes = $perfil->puntajesPorCodigo();
        $nombres = array_column($perfil->areas_evaluadas_json, 'nombre', 'codigo');
        $modulos = $this->catalogo();
        $catalogo = $modulos->map(fn (ModuloContenido $m) => new ModuloCatalogo(
            $m->id_modulo, $m->area->codigo, $m->nombre, $m->nivel, $m->recursos_activos_count > 0
        ))->all();

        $fallback = fn () => $this->planComoArreglo($this->generador->generar($puntajes, $nombres, $catalogo));

        if ($soloReglas) {
            $inicio = hrtime(true);
            $datos = $fallback();
            $latencia = (int) round((hrtime(true) - $inicio) / 1_000_000);
            BitacoraIA::create([
                'tipo' => ClavePrompt::Ruta->value, 'estado' => EstadoSolicitudIA::Fallback, 'latencia_ms' => $latencia,
                'intentos' => 0, 'codigo_error' => 'espera_excedida', 'referencia_tipo' => 'perfil', 'referencia_id' => $perfil->id_perfil,
            ]);
            $resultado = new ResultadoIA($datos, OrigenGeneracion::Reglas, null, null, $latencia, 'espera_excedida');
        } else {
            $disponibles = $modulos->filter(fn (ModuloContenido $m) => $m->recursos_activos_count > 0);
            $areaPorModulo = $disponibles->mapWithKeys(fn (ModuloContenido $m) => [$m->id_modulo => $m->area->codigo])->all();
            $obligatorias = $this->generador->areasObligatorias($puntajes);
            $maximo = (int) config('plataforma.rutas.max_modulos', 10);

            $entrada = $this->minimizador->entradaRuta(
                $perfil->areas_evaluadas_json,
                $perfil->nivel_general->value,
                $perfil->puntaje_global,
                $disponibles->map(fn (ModuloContenido $m) => [
                    'id_modulo' => $m->id_modulo, 'area' => $m->area->codigo, 'nombre' => $m->nombre, 'nivel' => $m->nivel->value,
                ])->values()->all(),
                $perfil->diagnostico->usuario?->postulacionActual()?->proceso?->puesto,
                $maximo,
                $obligatorias,
            );

            $resultado = $this->ia->generar(
                ClavePrompt::Ruta,
                $entrada,
                fn (array $datos) => $this->validador->validarRuta($datos, array_keys($areaPorModulo), $areaPorModulo, $obligatorias, $maximo),
                $fallback,
                'perfil',
                $perfil->id_perfil,
            );
        }

        $ruta = $this->persistir($perfil, $resultado, $this->generador->todasAvanzadas($puntajes));

        if ($ruta->es_ruta_avanzada) {
            Notification::send(Usuario::where('rol', Rol::AdminRrhh->value)->where('activo', true)->get(), new PerfilMaximoNotificacion($perfil));
        }

        return $ruta;
    }

    /** Si la cola no generó la ruta dentro del tiempo máximo, se genera por reglas (recuperación ante fallos). */
    public function asegurarRuta(PerfilCompetencia $perfil): ?RutaAprendizaje
    {
        if ($ruta = $perfil->ruta()->first()) {
            return $ruta;
        }
        $espera = (int) config('plataforma.rutas.espera_maxima_segundos', 120);
        if ($perfil->created_at && $perfil->created_at->lt(now()->subSeconds($espera))) {
            return $this->generarParaPerfil($perfil, true);
        }

        return null;
    }

    /** @return Collection<int,ModuloContenido> */
    private function catalogo(): Collection
    {
        return ModuloContenido::activos()
            ->with('area')
            ->withCount(['recursos as recursos_activos_count' => fn ($q) => $q->where('estado', EstadoRecurso::Activo->value)])
            ->orderBy('id_area')->orderBy('orden')
            ->get();
    }

    private function persistir(PerfilCompetencia $perfil, ResultadoIA $resultado, bool $esAvanzada): RutaAprendizaje
    {
        try {
            $ruta = DB::transaction(function () use ($perfil, $resultado, $esAvanzada) {
                $ruta = RutaAprendizaje::create([
                    'id_perfil' => $perfil->id_perfil,
                    'estado' => EstadoRuta::Activa,
                    'origen' => $resultado->origen,
                    'narrativa' => $resultado->datos['narrativa'],
                    'fortalezas_json' => $resultado->datos['fortalezas'],
                    'areas_mejora_json' => $resultado->datos['areas_mejora'],
                    'es_ruta_avanzada' => $esAvanzada,
                    'modelo_ia' => $resultado->modelo,
                    'version_prompt' => $resultado->versionPrompt,
                    'tiempo_generacion_ms' => $resultado->latenciaMs,
                ]);

                foreach (array_values($resultado->datos['modulos']) as $i => $modulo) {
                    RutaModulo::create([
                        'id_ruta' => $ruta->id_ruta,
                        'id_modulo' => $modulo['id_modulo'],
                        'orden' => $i + 1,
                        'prioridad' => $modulo['prioridad'],
                        'justificacion' => mb_substr($modulo['justificacion'], 0, 500),
                    ]);
                }

                return $ruta;
            });
        } catch (QueryException $e) {
            // Otra ejecución (cola o recuperación) ya registró la ruta del perfil: se reutiliza.
            if (($e->errorInfo[0] ?? null) === '23000' && ($existente = $perfil->ruta()->first())) {
                return $existente;
            }
            throw $e;
        }

        $this->bitacora->registrar('ruta_generada', $perfil->diagnostico->usuario, $ruta, [
            'origen' => $resultado->origen->value,
            'modulos' => count($resultado->datos['modulos']),
            'version_prompt' => $resultado->versionPrompt,
            'codigo_fallback' => $resultado->codigoError,
        ]);

        return $ruta;
    }

    /** @return array{modulos:array<int,array{id_modulo:int,prioridad:string,justificacion:string}>,narrativa:string,fortalezas:string[],areas_mejora:string[]} */
    private function planComoArreglo(PlanRuta $plan): array
    {
        return [
            'modulos' => array_map(fn ($m) => [
                'id_modulo' => $m->idModulo,
                'prioridad' => $m->prioridad->value,
                'justificacion' => $m->justificacion,
            ], $plan->modulos),
            'narrativa' => $plan->narrativa,
            'fortalezas' => $plan->fortalezas,
            'areas_mejora' => $plan->areasMejora,
        ];
    }
}
