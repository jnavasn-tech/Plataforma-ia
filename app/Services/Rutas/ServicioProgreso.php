<?php

namespace App\Services\Rutas;

use App\Dominio\Evaluacion\CalificadorEvaluacion;
use App\Enums\EstadoRecurso;
use App\Enums\EstadoRuta;
use App\Enums\EstadoRutaModulo;
use App\Enums\Rol;
use App\Models\ProgresoRecurso;
use App\Models\RecursoAprendizaje;
use App\Models\RutaAprendizaje;
use App\Models\RutaModulo;
use App\Models\Usuario;
use App\Notifications\RecursoNoDisponibleNotificacion;
use App\Notifications\RutaCompletadaNotificacion;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Caso de uso CU-04 "Seguir ruta de aprendizaje" (RF-05): acceso, avance de recursos y conclusión de módulos. */
class ServicioProgreso
{
    public function __construct(
        private readonly CalificadorEvaluacion $calificador,
        private readonly RegistradorBitacora $bitacora,
    ) {
    }

    /** Un módulo es accesible si es el primero o si el módulo anterior está concluido. */
    public function accesible(RutaModulo $rutaModulo): bool
    {
        $anterior = RutaModulo::where('id_ruta', $rutaModulo->id_ruta)
            ->where('orden', '<', $rutaModulo->orden)
            ->orderByDesc('orden')
            ->first();

        return $anterior === null || $anterior->estado === EstadoRutaModulo::Completado;
    }

    public function iniciarRecurso(Usuario $usuario, RutaModulo $rutaModulo, RecursoAprendizaje $recurso): ProgresoRecurso
    {
        $this->verificarRecurso($rutaModulo, $recurso);

        $progreso = ProgresoRecurso::firstOrCreate(
            ['id_ruta_modulo' => $rutaModulo->id_ruta_modulo, 'id_recurso' => $recurso->id_recurso],
            ['id_usuario' => $usuario->id_usuario, 'iniciado_en' => now()]
        );

        if ($rutaModulo->estado === EstadoRutaModulo::Pendiente) {
            $rutaModulo->update(['estado' => EstadoRutaModulo::EnCurso]);
        }

        return $progreso;
    }

    public function completarRecurso(Usuario $usuario, RutaModulo $rutaModulo, RecursoAprendizaje $recurso): void
    {
        $progreso = $this->iniciarRecurso($usuario, $rutaModulo, $recurso);
        if ($progreso->completado_en === null) {
            $progreso->update(['completado_en' => now()]);
            $this->bitacora->registrar('recurso_completado', $usuario, $recurso, ['id_ruta_modulo' => $rutaModulo->id_ruta_modulo]);
        }
        $this->recalcularAvance($rutaModulo);
    }

    /**
     * Flujo alterno de CU-04: el aspirante reporta un recurso no disponible; se alerta al administrador y se
     * ofrecen hasta dos recursos alternativos del mismo módulo o, en su defecto, de la misma área.
     *
     * @return Collection<int,RecursoAprendizaje>
     */
    public function reportarNoDisponible(Usuario $usuario, RutaModulo $rutaModulo, RecursoAprendizaje $recurso): Collection
    {
        $progreso = $this->iniciarRecurso($usuario, $rutaModulo, $recurso);
        $progreso->update(['reportado_no_disponible_en' => now()]);

        Notification::send(
            Usuario::where('rol', Rol::AdminRrhh->value)->where('activo', true)->get(),
            new RecursoNoDisponibleNotificacion($recurso)
        );
        $this->bitacora->registrar('recurso_reportado_no_disponible', $usuario, $recurso);

        $alternativas = RecursoAprendizaje::where('id_modulo', $recurso->id_modulo)
            ->where('estado', EstadoRecurso::Activo->value)
            ->whereKeyNot($recurso->id_recurso)
            ->limit(2)->get();

        if ($alternativas->count() < 2) {
            $alternativas = $alternativas->concat(
                RecursoAprendizaje::where('estado', EstadoRecurso::Activo->value)
                    ->whereKeyNot($recurso->id_recurso)
                    ->whereNotIn('id_recurso', $alternativas->pluck('id_recurso'))
                    ->whereHas('modulo', fn ($q) => $q->where('id_area', $recurso->modulo->id_area))
                    ->limit(2 - $alternativas->count())->get()
            );
        }

        return $alternativas;
    }

    /** La evaluación se habilita al completar todos los recursos activos del módulo y mientras queden intentos. */
    public function evaluacionHabilitada(RutaModulo $rutaModulo): bool
    {
        if ($rutaModulo->estado === EstadoRutaModulo::Completado || ! $this->accesible($rutaModulo)) {
            return false;
        }
        $intentos = $rutaModulo->evaluaciones()->count();
        $aprobado = $rutaModulo->evaluaciones()->where('aprobado', true)->exists();

        return $this->recursosPendientes($rutaModulo) === 0 && $this->calificador->puedeIntentar($intentos, $aprobado);
    }

    public function recursosPendientes(RutaModulo $rutaModulo): int
    {
        $activos = $rutaModulo->modulo->recursosActivos()->pluck('id_recurso');
        $completados = ProgresoRecurso::where('id_ruta_modulo', $rutaModulo->id_ruta_modulo)
            ->whereIn('id_recurso', $activos)->whereNotNull('completado_en')->count();

        return max(0, $activos->count() - $completados);
    }

    public function concluirModulo(Usuario $usuario, RutaModulo $rutaModulo): void
    {
        $rutaModulo->update(['estado' => EstadoRutaModulo::Completado, 'porcentaje_avance' => 100, 'completado_en' => now()]);
        $this->bitacora->registrar('modulo_concluido', $usuario, $rutaModulo);

        $ruta = $rutaModulo->ruta()->with('modulos')->first();
        if ($ruta->modulos->every(fn (RutaModulo $m) => $m->estado === EstadoRutaModulo::Completado)) {
            $ruta->update(['estado' => EstadoRuta::Completada, 'completada_en' => now()]);
            $this->bitacora->registrar('ruta_completada', $usuario, $ruta);
            Notification::send(
                Usuario::where('rol', Rol::AdminRrhh->value)->where('activo', true)->get(),
                new RutaCompletadaNotificacion($usuario, $ruta)
            );
        }
    }

    public function recalcularAvance(RutaModulo $rutaModulo): void
    {
        if ($rutaModulo->estado === EstadoRutaModulo::Completado) {
            return;
        }
        $totalRecursos = $rutaModulo->modulo->recursosActivos()->count();
        $completados = $totalRecursos - $this->recursosPendientes($rutaModulo);
        // Los recursos aportan el avance y la evaluación concluye el módulo (unidad adicional).
        $rutaModulo->update(['porcentaje_avance' => round($completados / ($totalRecursos + 1) * 100, 2)]);
    }

    /**
     * Puntaje formativo por área: promedio de la mejor calificación obtenida en cada módulo evaluado del área.
     * Se presenta junto al puntaje del diagnóstico sin modificar el pretest (CU-04, pasos 7 y 8).
     *
     * @return array<string,float> nombre de área => puntaje formativo
     */
    public function puntajesFormativos(RutaAprendizaje $ruta): array
    {
        $porArea = [];
        foreach ($ruta->modulos()->with(['modulo.area', 'evaluaciones'])->get() as $rutaModulo) {
            if ($rutaModulo->evaluaciones->isEmpty()) {
                continue;
            }
            $porArea[$rutaModulo->modulo->area->nombre][] = (float) $rutaModulo->evaluaciones->max('calificacion');
        }

        return array_map(fn (array $notas) => round(array_sum($notas) / count($notas), 2), $porArea);
    }

    private function verificarRecurso(RutaModulo $rutaModulo, RecursoAprendizaje $recurso): void
    {
        if ($recurso->id_modulo !== $rutaModulo->id_modulo || $recurso->estado !== EstadoRecurso::Activo) {
            throw new NotFoundHttpException();
        }
    }
}
