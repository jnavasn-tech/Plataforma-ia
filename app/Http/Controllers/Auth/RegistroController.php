<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistroRequest;
use App\Services\Usuarios\ServicioRegistro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Pantalla 2: registro de aspirantes. RF-01, CU-01. */
class RegistroController extends Controller
{
    public function create(): View
    {
        return view('auth.registro');
    }

    public function store(RegistroRequest $request, ServicioRegistro $servicio): RedirectResponse
    {
        $usuario = $servicio->registrarAspirante($request->validated());

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('estado', 'Su cuenta fue creada. Le enviamos un correo para confirmar su dirección electrónica.');
    }
}
