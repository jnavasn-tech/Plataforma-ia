<?php

namespace App\Providers;

use App\Dominio\Diagnostico\MotorAdaptativo;
use App\Dominio\Evaluacion\CalificadorEvaluacion;
use App\Dominio\Perfil\ClasificadorNivel;
use App\Dominio\Rutas\GeneradorRutaReglas;
use App\Services\IA\ClienteIA;
use App\Services\IA\ClienteOpenAI;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

/** Composición de dependencias: las reglas de dominio reciben sus parámetros desde config/plataforma.php. */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MotorAdaptativo::class, fn () => new MotorAdaptativo((int) config('plataforma.diagnostico.items_por_area', 6)));

        $this->app->singleton(ClasificadorNivel::class, fn () => new ClasificadorNivel(
            (float) config('plataforma.niveles.umbral_intermedio', 50),
            (float) config('plataforma.niveles.umbral_avanzado', 80),
        ));

        $this->app->singleton(GeneradorRutaReglas::class, fn ($app) => new GeneradorRutaReglas(
            $app->make(ClasificadorNivel::class),
            (int) config('plataforma.rutas.max_modulos', 10),
        ));

        $this->app->singleton(CalificadorEvaluacion::class, fn () => new CalificadorEvaluacion(
            (float) config('plataforma.evaluacion.umbral_aprobacion', 70),
            (int) config('plataforma.evaluacion.max_intentos', 3),
        ));

        // Proveedor de IA intercambiable por configuración (por ejemplo, un microservicio externo).
        $this->app->bind(ClienteIA::class, ClienteOpenAI::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Política de contraseñas (CU-08): mínimo 8 caracteres, mayúsculas y minúsculas, y al menos un número.
        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers());
    }
}
