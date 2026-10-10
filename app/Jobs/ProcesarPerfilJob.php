<?php

namespace App\Jobs;

use App\Enums\TipoDiagnostico;
use App\Models\PerfilCompetencia;
use App\Services\Perfil\ServicioPerfil;
use App\Services\Rutas\ServicioRutas;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Procesamiento asíncrono posterior al diagnóstico: interpretación del perfil y, en el pretest,
 * generación de la ruta (CU-03 activado automáticamente al finalizar CU-02).
 * La ejecución en cola evita que la latencia del proveedor de IA afecte el tiempo de respuesta del reactivo (RNF-02).
 */
class ProcesarPerfilJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 90;

    public function __construct(public int $idPerfil)
    {
        $this->afterCommit();
    }

    public function handle(ServicioPerfil $servicioPerfil, ServicioRutas $servicioRutas): void
    {
        $perfil = PerfilCompetencia::with('diagnostico')->find($this->idPerfil);
        if ($perfil === null) {
            return;
        }

        $servicioPerfil->interpretar($perfil);

        if ($perfil->diagnostico->tipo === TipoDiagnostico::Pretest) {
            $servicioRutas->generarParaPerfil($perfil);
        }
    }

    /** Si el trabajo falla definitivamente, la ruta se genera por reglas para no dejar al aspirante sin ruta. */
    public function failed(Throwable $excepcion): void
    {
        Log::channel('ia')->error('ProcesarPerfilJob falló', ['id_perfil' => $this->idPerfil, 'error' => class_basename($excepcion)]);

        try {
            $perfil = PerfilCompetencia::with('diagnostico')->find($this->idPerfil);
            if ($perfil && $perfil->diagnostico->tipo === TipoDiagnostico::Pretest) {
                app(ServicioRutas::class)->generarParaPerfil($perfil, true);
            }
        } catch (Throwable $e) {
            Log::channel('ia')->critical('No fue posible generar la ruta por reglas', ['id_perfil' => $this->idPerfil, 'error' => class_basename($e)]);
        }
    }
}
