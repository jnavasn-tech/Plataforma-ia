<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /** Ruta posterior a la autenticación; redirige al panel según el rol. */
    public const HOME = '/panel';

    public function boot(): void
    {
        // Limitación de intentos y de frecuencia por tipo de operación.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id_usuario ?: $request->ip()));
        RateLimiter::for('registro', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('recuperacion', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('diagnostico', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id_usuario ?: $request->ip()));
        RateLimiter::for('evaluacion', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id_usuario ?: $request->ip()));
        RateLimiter::for('reportes', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id_usuario ?: $request->ip()));

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
