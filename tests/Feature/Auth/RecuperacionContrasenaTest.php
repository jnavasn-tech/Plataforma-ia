<?php

namespace Tests\Feature\Auth;

use App\Notifications\RestablecerContrasenaNotificacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-AUT-11 a CP-AUT-13. RF-01, CU-01 y CU-08 (flujo alterno: recuperación de contraseña). */
class RecuperacionContrasenaTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    /** CP-AUT-11 */
    public function test_respuesta_identica_para_correo_existente_e_inexistente(): void
    {
        Notification::fake();
        $usuario = $this->aspirante();
        $mensaje = 'Si el correo está registrado, recibirá un enlace para restablecer su contraseña.';

        $this->post(route('password.email'), ['correo' => $usuario->correo])->assertSessionHas('estado', $mensaje);
        $this->post(route('password.email'), ['correo' => 'desconocido@example.com'])->assertSessionHas('estado', $mensaje);

        Notification::assertSentTo($usuario, RestablecerContrasenaNotificacion::class);
        Notification::assertSentTimes(RestablecerContrasenaNotificacion::class, 1);
    }

    /** CP-AUT-12 */
    public function test_restablece_la_contrasena_con_token_valido(): void
    {
        $usuario = $this->aspirante();
        $token = Password::broker()->createToken($usuario);

        $this->post(route('password.update'), [
            'token' => $token, 'correo' => $usuario->correo,
            'contrasena' => 'NuevaClave2026', 'contrasena_confirmation' => 'NuevaClave2026',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NuevaClave2026', $usuario->fresh()->contrasena_hash));
    }

    /** CP-AUT-13 */
    public function test_token_invalido_es_rechazado(): void
    {
        $usuario = $this->aspirante();

        $this->post(route('password.update'), [
            'token' => 'token-invalido', 'correo' => $usuario->correo,
            'contrasena' => 'NuevaClave2026', 'contrasena_confirmation' => 'NuevaClave2026',
        ])->assertSessionHasErrors('correo');

        $this->assertFalse(Hash::check('NuevaClave2026', $usuario->fresh()->contrasena_hash));
    }
}
