<?php

namespace Database\Seeders;

use App\Enums\EstadoPostulacion;
use App\Enums\Rol;
use App\Models\Postulacion;
use App\Models\ProcesoSeleccion;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Usuarios ficticios de demostración (sin datos personales reales).
 * Las contraseñas se leen de DEMO_ADMIN_PASSWORD y DEMO_ASPIRANTE_PASSWORD; si no existen,
 * se generan aleatoriamente y se muestran una sola vez en la consola. No ejecutar en producción.
 */
class UsuariosDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('UsuariosDemoSeeder no se ejecuta en producción.');

            return;
        }

        $claveAdmin = env('DEMO_ADMIN_PASSWORD') ?: $this->generar('administrador');
        $claveAspirante = env('DEMO_ASPIRANTE_PASSWORD') ?: $this->generar('aspirantes');

        $this->usuario('Administrador RRHH (demostración)', 'admin.demo@example.com', $claveAdmin, Rol::AdminRrhh, 'Coordinador de RRHH');
        $proceso = ProcesoSeleccion::vigente();

        foreach ([1, 2] as $n) {
            $aspirante = $this->usuario("Aspirante Demostración {$n}", "aspirante{$n}.demo@example.com", $claveAspirante, Rol::Aspirante);
            if ($proceso) {
                Postulacion::firstOrCreate(['id_usuario' => $aspirante->id_usuario, 'id_proceso' => $proceso->id_proceso], ['estado' => EstadoPostulacion::Pendiente]);
            }
        }
    }

    private function usuario(string $nombre, string $correo, string $clave, Rol $rol, ?string $cargo = null): Usuario
    {
        $usuario = Usuario::firstOrNew(['correo' => $correo]);
        $usuario->fill(['nombre' => $nombre, 'cargo' => $cargo]);
        $usuario->forceFill([
            'contrasena_hash' => Hash::make($clave),
            'rol' => $rol,
            'email_verified_at' => now(),
            'consentimiento_privacidad_at' => now(),
            'version_aviso_privacidad' => config('plataforma.privacidad.version_aviso'),
            'activo' => true,
        ])->save();

        return $usuario;
    }

    private function generar(string $tipo): string
    {
        $clave = Str::password(14, true, true, false).'Aa1';
        $this->command?->warn("Contraseña generada para {$tipo} de demostración: {$clave} (guárdela; no se volverá a mostrar).");

        return $clave;
    }
}
