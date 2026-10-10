<?php

namespace Tests\Feature\Aspirante;

use App\Notifications\ContrasenaActualizadaNotificacion;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-CTA-01 a CP-CTA-04. CU-08 "Actualizar perfil y contraseña" (flujo principal y alternos). */
class CuentaUsuarioTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    /** CP-CTA-01 */
    public function test_actualiza_los_datos_del_perfil(): void
    {
        $usuario = $this->aspirante();

        $this->actingAs($usuario)->put(route('perfil.update'), ['nombre' => 'Nombre Actualizado', 'telefono' => '5555 1234', 'cargo' => 'Ignorado'])
            ->assertSessionHas('estado', 'Sus datos han sido actualizados correctamente.');

        $usuario->refresh();
        $this->assertSame('Nombre Actualizado', $usuario->nombre);
        $this->assertSame('5555 1234', $usuario->telefono);
        $this->assertNull($usuario->cargo, 'El aspirante no puede modificar el campo cargo.');
    }

    /** CP-CTA-02 */
    public function test_contrasena_actual_incorrecta_es_rechazada(): void
    {
        $usuario = $this->aspirante();

        $this->actingAs($usuario)->put(route('perfil.contrasena'), [
            'contrasena_actual' => 'NoEsLaActual1', 'contrasena' => 'NuevaClave2026', 'contrasena_confirmation' => 'NuevaClave2026',
        ])->assertSessionHasErrorsIn('contrasena', ['contrasena_actual' => 'La contraseña actual es incorrecta. Verifique e intente nuevamente.']);

        $this->assertTrue(Hash::check(UsuarioFactory::CLAVE_PRUEBA, $usuario->fresh()->contrasena_hash));
    }

    /** CP-CTA-03 */
    public function test_nueva_contrasena_que_no_cumple_requisitos_es_rechazada(): void
    {
        $usuario = $this->aspirante();

        $this->actingAs($usuario)->put(route('perfil.contrasena'), [
            'contrasena_actual' => UsuarioFactory::CLAVE_PRUEBA, 'contrasena' => 'debil', 'contrasena_confirmation' => 'debil',
        ])->assertSessionHasErrorsIn('contrasena', 'contrasena');
    }

    /** CP-CTA-04 */
    public function test_cambio_exitoso_invalida_otras_sesiones_y_notifica(): void
    {
        Notification::fake();
        config(['session.driver' => 'database']);
        $usuario = $this->aspirante();
        DB::table('sessions')->insert(['id' => 'sesion-otro-dispositivo', 'user_id' => $usuario->id_usuario, 'payload' => 'x', 'last_activity' => time()]);
        $tokenAnterior = $usuario->remember_token;

        $this->actingAs($usuario)->put(route('perfil.contrasena'), [
            'contrasena_actual' => UsuarioFactory::CLAVE_PRUEBA, 'contrasena' => 'NuevaClave2026', 'contrasena_confirmation' => 'NuevaClave2026',
        ])->assertSessionHasNoErrors();

        $usuario->refresh();
        $this->assertTrue(Hash::check('NuevaClave2026', $usuario->contrasena_hash));
        $this->assertNotSame($tokenAnterior, $usuario->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-otro-dispositivo']);
        Notification::assertSentTo($usuario, ContrasenaActualizadaNotificacion::class);
    }
}
