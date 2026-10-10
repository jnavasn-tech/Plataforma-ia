<?php

namespace App\Http\Controllers;

use App\Services\Usuarios\ServicioCuenta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password as Restablecimiento;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Pantalla 17: perfil del usuario. CU-08 "Actualizar perfil y contraseña" (aspirante y administrador). */
class PerfilUsuarioController extends Controller
{
    public function __construct(private readonly ServicioCuenta $servicio)
    {
    }

    public function edit(Request $request): View
    {
        return view('perfil.editar', ['usuario' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:120', "regex:/^[\pL\s'.-]+$/u"],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'cargo' => ['nullable', 'string', 'max:100'],
        ]);

        $this->servicio->actualizarDatos($request->user(), $datos);

        return back()->with('estado', 'Sus datos han sido actualizados correctamente.');
    }

    public function actualizarContrasena(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('contrasena', [
            'contrasena_actual' => ['required', 'string'],
            'contrasena' => ['required', 'string', 'confirmed', 'different:contrasena_actual', Password::defaults()],
        ], [
            'contrasena.different' => 'La nueva contraseña debe ser distinta de la actual.',
        ]);

        $this->servicio->cambiarContrasena($request->user(), $datos['contrasena_actual'], $datos['contrasena'], $request->session()->getId());

        return back()->with('estado', 'Sus datos han sido actualizados correctamente. Se cerraron las sesiones en otros dispositivos.');
    }

    /** CU-08, flujo alterno: el usuario no recuerda su contraseña actual y solicita un enlace a su correo. */
    public function enviarEnlace(Request $request): RedirectResponse
    {
        Restablecimiento::broker()->sendResetLink(['correo' => $request->user()->correo]);

        return back()->with('estado', 'Le enviamos un enlace de restablecimiento a su correo registrado.');
    }
}
