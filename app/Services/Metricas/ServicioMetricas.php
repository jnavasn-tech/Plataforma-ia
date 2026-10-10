<?php

namespace App\Services\Metricas;

use App\Enums\EstadoDiagnostico;
use App\Enums\EstadoRuta;
use App\Enums\EstadoSolicitudIA;
use App\Enums\Rol;
use App\Enums\TipoDiagnostico;
use App\Models\BitacoraIA;
use App\Models\Diagnostico;
use App\Models\PerfilCompetencia;
use App\Models\RespuestaDiagnostico;
use App\Models\RutaAprendizaje;
use App\Models\Usuario;
use App\Models\ValoracionModulo;

/**
 * Métricas del panel administrativo (RF-08) calculadas exclusivamente a partir de los datos registrados.
 * Cuando no existen datos, el valor es null y la interfaz muestra "Sin datos".
 * Fórmulas alineadas con los indicadores del Capítulo I.
 */
class ServicioMetricas
{
    public function resumen(): array
    {
        return [
            'aspirantes' => Usuario::where('rol', Rol::Aspirante->value)->count(),
            'diagnosticos' => $this->diagnosticos(),
            'rutas' => $this->rutas(),
            'tiempo_reactivo' => $this->tiempoReactivo(),
            'precision' => $this->precision(),
            'mejora' => $this->mejoraPretestPostest(),
            'ia' => $this->ia(),
            'satisfaccion' => $this->satisfaccion(),
        ];
    }

    private function diagnosticos(): array
    {
        $conteo = fn (string $tipo, EstadoDiagnostico $estado) => Diagnostico::where('tipo', $tipo)->where('estado', $estado->value)->count();

        return [
            'pretest_completados' => $conteo('pretest', EstadoDiagnostico::Completado),
            'postest_completados' => $conteo('postest', EstadoDiagnostico::Completado),
            'en_progreso' => Diagnostico::whereIn('estado', ['iniciado', 'en_progreso'])->count(),
            'abandonados' => Diagnostico::where('estado', EstadoDiagnostico::Abandonado->value)->count(),
        ];
    }

    /** Tasa de retención = rutas completadas / rutas generadas × 100. */
    private function rutas(): array
    {
        $generadas = RutaAprendizaje::count();
        $completadas = RutaAprendizaje::where('estado', EstadoRuta::Completada->value)->count();

        return [
            'generadas' => $generadas,
            'por_ia' => RutaAprendizaje::where('origen', 'ia')->count(),
            'por_reglas' => RutaAprendizaje::where('origen', 'reglas')->count(),
            'completadas' => $completadas,
            'tasa_retencion' => $generadas > 0 ? round($completadas / $generadas * 100, 2) : null,
        ];
    }

    /** RNF-02: tiempo de procesamiento del servidor por reactivo (promedio, percentil 95 y % bajo 5 s). */
    private function tiempoReactivo(): array
    {
        $tiempos = RespuestaDiagnostico::whereNotNull('tiempo_servidor_ms')->pluck('tiempo_servidor_ms')->map(fn ($v) => (int) $v)->all();
        $n = count($tiempos);

        return [
            'n' => $n,
            'promedio_ms' => $n ? round(array_sum($tiempos) / $n, 2) : null,
            'p95_ms' => $n ? $this->percentil($tiempos, 95) : null,
            'porcentaje_bajo_5s' => $n ? round(count(array_filter($tiempos, fn ($t) => $t < 5000)) / $n * 100, 2) : null,
        ];
    }

    /** Precisión = perfiles cuyo nivel coincide con la evaluación de control / perfiles con control × 100. */
    private function precision(): array
    {
        $conControl = PerfilCompetencia::whereNotNull('nivel_control')->get(['nivel_general', 'nivel_control']);
        $n = $conControl->count();
        $coinciden = $conControl->filter(fn ($p) => $p->nivel_general === $p->nivel_control)->count();

        return ['n' => $n, 'porcentaje' => $n ? round($coinciden / $n * 100, 2) : null];
    }

    /** Mejora = promedio de (puntaje postest − puntaje pretest) en puntos porcentuales. */
    private function mejoraPretestPostest(): array
    {
        $diferencias = [];
        $postests = Diagnostico::with('perfil')->where('tipo', TipoDiagnostico::Postest->value)->where('estado', 'completado')->get();
        foreach ($postests as $postest) {
            $pretest = Diagnostico::with('perfil')
                ->where('id_usuario', $postest->id_usuario)
                ->where('tipo', TipoDiagnostico::Pretest->value)
                ->where('estado', 'completado')
                ->latest('finalizado_en')->first();
            if ($pretest?->perfil && $postest->perfil) {
                $diferencias[] = $postest->perfil->puntaje_global - $pretest->perfil->puntaje_global;
            }
        }
        $n = count($diferencias);

        return ['n' => $n, 'promedio_pp' => $n ? round(array_sum($diferencias) / $n, 2) : null];
    }

    private function ia(): array
    {
        $total = BitacoraIA::count();
        $exito = BitacoraIA::where('estado', EstadoSolicitudIA::Exito->value)->count();
        $fallback = BitacoraIA::where('estado', EstadoSolicitudIA::Fallback->value)->count();
        $latencias = BitacoraIA::where('estado', EstadoSolicitudIA::Exito->value)->whereNotNull('latencia_ms')->pluck('latencia_ms')->map(fn ($v) => (int) $v)->all();

        return [
            'total' => $total,
            'exito' => $exito,
            'fallback' => $fallback,
            'deshabilitada' => BitacoraIA::where('estado', EstadoSolicitudIA::Deshabilitada->value)->count(),
            'porcentaje_fallback' => $total ? round($fallback / $total * 100, 2) : null,
            'latencia_promedio_ms' => $latencias ? round(array_sum($latencias) / count($latencias), 2) : null,
            'latencia_p95_ms' => $latencias ? $this->percentil($latencias, 95) : null,
        ];
    }

    private function satisfaccion(): array
    {
        $n = ValoracionModulo::count();

        return ['n' => $n, 'promedio' => $n ? round((float) ValoracionModulo::avg('puntuacion'), 2) : null];
    }

    /** @param int[] $valores */
    private function percentil(array $valores, int $p): int
    {
        sort($valores);
        $indice = max(0, (int) ceil($p / 100 * count($valores)) - 1);

        return $valores[$indice];
    }
}
