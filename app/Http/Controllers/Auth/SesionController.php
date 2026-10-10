<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\InicioSesionRequest;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pantalla 3: inicio y cierre de sesión. RF-01, CU-01.
 * Tras 5 intentos fallidos para la misma combinación correo/IP, el acceso se bloquea 15 minutos.
 */
class SesionController extends Controller
{
    private const MAX_INTENTOS = 5;

    private const BLOQUEO_SEGUNDOS = 900;

    public function __construct(private readonly RegistradorBitacora $bitacora)
    {
    }

    public function create(): View
    {
        return view('auth.iniciar-sesion');
    }

    public function store(InicioSesionRequest $request): RedirectResponse
    {
        $clave = 'login:'.sha1($request->input('correo').'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            $minutos = (int) ceil(RateLimiter::availableIn($clave) / 60);
            $this->bitacora->registrar('inicio_sesion_bloqueado', null, null, [], $request);
            throw ValidationException::withMessages([
                'correo' => "Demasiados intentos fallidos. Por seguridad, intente nuevamente en {$minutos} minutos.",
            ]);
        }

        $credenciales = ['correo' => $request->input('correo'), 'password' => $request->input('contrasena'), 'activo' => true];
        if (! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            RateLimiter::hit($clave, self::BLOQUEO_SEGUNDOS);
            $this->bitacora->registrar('inicio_sesion_fallido', null, null, ['intentos' => RateLimiter::attempts($clave)], $request);
            throw ValidationException::withMessages([
                'correo' => 'Credenciales incorrectas. Verifique su correo y contraseña.',
            ]);
        }

        RateLimiter::clear($clave);
        $request->session()->regenerate();

        $usuario = $request->user();
        $usuario->forceFill(['ultimo_acceso_at' => now()])->save();
        $this->bitacora->registrar('inicio_sesion', $usuario, null, [], $request);

        return redirect()->intended(route('panel'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->bitacora->registrar('cierre_sesion', $request->user(), null, [], $request);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio')->with('estado', 'Su sesión se cerró correctamente.');
    }
}
