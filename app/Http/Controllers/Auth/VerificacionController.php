<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Confirmación del correo electrónico (CU-01, pasos 5 y 6). */
class VerificacionController extends Controller
{
    public function aviso(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('panel')
            : view('auth.verificar-correo');
    }

    public function verificar(EmailVerificationRequest $request, RegistradorBitacora $bitacora): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
            $bitacora->registrar('correo_verificado', $request->user(), $request->user());
        }

        return redirect()->route('panel')->with('estado', 'Su correo fue confirmado y su cuenta está activa.');
    }

    public function reenviar(Request $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('estado', 'Le enviamos un nuevo enlace de confirmación.');
    }
}
