<?php

namespace App\Providers;

use App\Models\Diagnostico;
use App\Models\Evaluacion;
use App\Models\RutaAprendizaje;
use App\Models\RutaModulo;
use App\Policies\DiagnosticoPolicy;
use App\Policies\EvaluacionPolicy;
use App\Policies\RutaAprendizajePolicy;
use App\Policies\RutaModuloPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/** Autorización por propiedad del recurso (un aspirante solo accede a sus propios datos). */
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Diagnostico::class => DiagnosticoPolicy::class,
        RutaAprendizaje::class => RutaAprendizajePolicy::class,
        RutaModulo::class => RutaModuloPolicy::class,
        Evaluacion::class => EvaluacionPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
