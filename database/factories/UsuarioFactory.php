<?php

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Fábrica de usuarios ficticios para pruebas automatizadas (dominio reservado example.com). */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public const CLAVE_PRUEBA = 'ClaveSegura123';

    public function definition(): array
    {
        return [
            'nombre' => 'Aspirante '.Str::upper(Str::random(5)),
            'correo' => 'aspirante.'.Str::lower(Str::random(10)).'@example.com',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Usuario $usuario) {
            $usuario->forceFill([
                'contrasena_hash' => $usuario->contrasena_hash ?? Hash::make(self::CLAVE_PRUEBA),
                'rol' => $usuario->rol ?? Rol::Aspirante,
                'email_verified_at' => $usuario->email_verified_at ?? now(),
                'consentimiento_privacidad_at' => now(),
                'version_aviso_privacidad' => '1.0',
                'activo' => $usuario->activo ?? true,
            ]);
        });
    }

    public function administrador(): static
    {
        return $this->state(fn () => ['nombre' => 'Administrador '.Str::upper(Str::random(5))])
            ->afterMaking(fn (Usuario $u) => $u->forceFill(['rol' => Rol::AdminRrhh, 'cargo' => 'Coordinador RRHH']));
    }

    public function sinVerificar(): static
    {
        return $this->afterMaking(fn (Usuario $u) => $u->forceFill(['email_verified_at' => null]));
    }
}
