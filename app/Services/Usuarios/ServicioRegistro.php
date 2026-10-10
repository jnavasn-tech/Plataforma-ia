<?php

namespace App\Services\Usuarios;

use App\Enums\EstadoPostulacion;
use App\Enums\Rol;
use App\Models\Postulacion;
use App\Models\ProcesoSeleccion;
use App\Models\Usuario;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Registro de aspirantes (CU-01): contraseña con bcrypt, consentimiento informado y asignación al proceso vigente. */
class ServicioRegistro
{
    public function __construct(private readonly RegistradorBitacora $bitacora)
    {
    }

    /** @param array{nombre:string,correo:string,contrasena:string,es_menor_edad?:bool,consentimiento_tutor?:bool} $datos */
    public function registrarAspirante(array $datos): Usuario
    {
        $usuario = DB::transaction(function () use ($datos) {
            $usuario = new Usuario([
                'nombre' => $datos['nombre'],
                'correo' => mb_strtolower(trim($datos['correo'])),
            ]);
            $usuario->forceFill([
                'contrasena_hash' => Hash::make($datos['contrasena']),
                'rol' => Rol::Aspirante,
                'es_menor_edad' => (bool) ($datos['es_menor_edad'] ?? false),
                'consentimiento_privacidad_at' => now(),
                'version_aviso_privacidad' => config('plataforma.privacidad.version_aviso'),
                'consentimiento_tutor_at' => ! empty($datos['consentimiento_tutor']) ? now() : null,
                'activo' => true,
            ])->save();

            if ($proceso = ProcesoSeleccion::vigente()) {
                Postulacion::create([
                    'id_usuario' => $usuario->id_usuario,
                    'id_proceso' => $proceso->id_proceso,
                    'estado' => EstadoPostulacion::Pendiente,
                ]);
            }

            return $usuario;
        });

        event(new Registered($usuario));
        $this->bitacora->registrar('registro', $usuario, $usuario);

        return $usuario;
    }
}
