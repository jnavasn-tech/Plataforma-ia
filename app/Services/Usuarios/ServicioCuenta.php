<?php

namespace App\Services\Usuarios;

use App\Models\Usuario;
use App\Notifications\ContrasenaActualizadaNotificacion;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Caso de uso CU-08 "Actualizar perfil y contraseña". */
class ServicioCuenta
{
    public function __construct(private readonly RegistradorBitacora $bitacora)
    {
    }

    /** @param array{nombre:string,telefono?:?string,cargo?:?string} $datos */
    public function actualizarDatos(Usuario $usuario, array $datos): void
    {
        $permitidos = $usuario->esAdministrador() ? ['nombre', 'telefono', 'cargo'] : ['nombre', 'telefono'];
        $usuario->fill(array_intersect_key($datos, array_flip($permitidos)))->save();
        $this->bitacora->registrar('perfil_actualizado', $usuario, $usuario, ['campos' => array_keys(array_intersect_key($datos, array_flip($permitidos)))]);
    }

    /**
     * Cambia la contraseña verificando la actual con bcrypt, invalida las sesiones en otros dispositivos
     * y notifica por correo.
     *
     * @throws ValidationException
     */
    public function cambiarContrasena(Usuario $usuario, string $actual, string $nueva, ?string $idSesionActual): void
    {
        if (! Hash::check($actual, $usuario->contrasena_hash)) {
            throw ValidationException::withMessages([
                'contrasena_actual' => 'La contraseña actual es incorrecta. Verifique e intente nuevamente.',
            ])->errorBag('contrasena');
        }

        $usuario->forceFill([
            'contrasena_hash' => Hash::make($nueva),
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $usuario->id_usuario)
                ->when($idSesionActual, fn ($q) => $q->where('id', '!=', $idSesionActual))
                ->delete();
        }

        $usuario->notify(new ContrasenaActualizadaNotificacion());
        $this->bitacora->registrar('contrasena_cambiada', $usuario, $usuario);
    }
}
