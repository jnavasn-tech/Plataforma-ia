<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaContrasena;
use Illuminate\View\View;

/**
 * Pantalla 4: recuperación de contraseña. La respuesta es la misma exista o no el correo,
 * para no revelar qué cuentas están registradas.
 */
class RecuperacionController extends Controller
{
    public function __construct(private readonly RegistradorBitacora $bitacora)
    {
    }

    public function create(): View
    {
        return view('auth.recuperar-contrasena');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate(['correo' => ['required', 'string', 'email', 'max:150']]);

        Password::broker()->sendResetLink(['correo' => mb_strtolower(trim($datos['correo']))]);
        $this->bitacora->registrar('recuperacion_solicitada', null, null, [], $request);

        return back()->with('estado', 'Si el correo está registrado, recibirá un enlace para restablecer su contraseña.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.restablecer-contrasena', ['token' => $token, 'correo' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'token' => ['required', 'string'],
            'correo' => ['required', 'string', 'email'],
            'contrasena' => ['required', 'string', 'confirmed', ReglaContrasena::defaults()],
        ]);

        $estado = Password::broker()->reset(
            [
                'correo' => mb_strtolower(trim($datos['correo'])),
                'password' => $datos['contrasena'],
                'password_confirmation' => $request->input('contrasena_confirmation'),
                'token' => $datos['token'],
            ],
            function (Usuario $usuario, string $contrasena) {
                $usuario->forceFill([
                    'contrasena_hash' => Hash::make($contrasena),
                    'remember_token' => Str::random(60),
                ])->save();
                DB::table('sessions')->where('user_id', $usuario->id_usuario)->delete();
                event(new PasswordReset($usuario));
                $this->bitacora->registrar('contrasena_restablecida', $usuario, $usuario);
            }
        );

        if ($estado === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('estado', 'Su contraseña fue restablecida. Ya puede iniciar sesión.');
        }

        return back()->withInput($request->only('correo'))
            ->withErrors(['correo' => 'El enlace de restablecimiento no es válido o ha vencido. Solicite uno nuevo.']);
    }
}
