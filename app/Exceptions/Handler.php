<?php

namespace App\Exceptions;

use App\Dominio\Diagnostico\BancoInsuficienteException;
use App\Dominio\Rutas\CatalogoInsuficienteException;
use App\Services\Diagnostico\DiagnosticoNoPermitidoException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Manejo centralizado de excepciones: mensajes comprensibles en español para el usuario,
 * registro técnico sin datos sensibles y ninguna traza expuesta en producción (APP_DEBUG=false).
 */
class Handler extends ExceptionHandler
{
    /** Campos que nunca se devuelven a la sesión tras un error de validación. */
    protected $dontFlash = [
        'contrasena',
        'contrasena_confirmation',
        'contrasena_actual',
        'password',
        'password_confirmation',
        'current_password',
    ];

    public function register(): void
    {
        $this->renderable(function (DiagnosticoNoPermitidoException $e, Request $request) {
            return redirect()->route('aspirante.panel')->with('aviso', $e->getMessage());
        });

        $this->renderable(function (BancoInsuficienteException $e, Request $request) {
            Log::warning('Banco de preguntas insuficiente', ['areas' => $e->areas]);

            return redirect()->route('aspirante.panel')
                ->with('aviso', 'El diagnóstico no está disponible temporalmente. El administrador fue notificado.');
        });

        $this->renderable(function (CatalogoInsuficienteException $e, Request $request) {
            Log::error('Catálogo insuficiente para generar rutas');

            return redirect()->route('aspirante.panel')
                ->with('aviso', 'Su ruta se generará en cuanto el catálogo de recursos esté disponible.');
        });
    }

    /** Contexto mínimo para los registros técnicos: nunca se incluyen datos personales del usuario. */
    protected function context(): array
    {
        return array_filter([
            'id_usuario' => auth()->id(),
            'ruta' => request()?->route()?->getName(),
        ]);
    }
}
