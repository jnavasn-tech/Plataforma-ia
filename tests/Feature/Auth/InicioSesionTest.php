<?php

namespace Tests\Feature\Auth;

use App\Models\Bitacora;
use App\Models\Usuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-AUT-05 a CP-AUT-10. RF-01, CU-01 (inicio de sesión, flujo alterno de contraseña incorrecta). */
class InicioSesionTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    /** CP-AUT-05 */
    public function test_inicio_de_sesion_redirige_segun_el_rol(): void
    {
        $aspirante = $this->aspirante();
        $admin = $this->administrador();

        $this->post(route('login.store'), ['correo' => $aspirante->correo, 'contrasena' => UsuarioFactory::CLAVE_PRUEBA])->assertRedirect(route('panel'));
        $this->get(route('panel'))->assertRedirect(route('aspirante.panel'));
        $this->assertNotNull($aspirante->fresh()->ultimo_acceso_at);

        $this->post(route('logout'));
        $this->post(route('login.store'), ['correo' => $admin->correo, 'contrasena' => UsuarioFactory::CLAVE_PRUEBA]);
        $this->get(route('panel'))->assertRedirect(route('admin.panel'));
    }

    /** CP-AUT-06 */
    public function test_credenciales_incorrectas_muestran_mensaje_generico(): void
    {
        $usuario = $this->aspirante();

        $this->post(route('login.store'), ['correo' => $usuario->correo, 'contrasena' => 'Incorrecta123'])
            ->assertSessionHasErrors(['correo' => 'Credenciales incorrectas. Verifique su correo y contraseña.']);
        $this->post(route('login.store'), ['correo' => 'noexiste@example.com', 'contrasena' => 'Incorrecta123'])
            ->assertSessionHasErrors(['correo' => 'Credenciales incorrectas. Verifique su correo y contraseña.']);
        $this->assertGuest();
    }

    /** CP-AUT-07 */
    public function test_bloquea_el_acceso_tras_cinco_intentos_fallidos(): void
    {
        $usuario = $this->aspirante();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['correo' => $usuario->correo, 'contrasena' => 'Incorrecta123']);
        }

        $respuesta = $this->post(route('login.store'), ['correo' => $usuario->correo, 'contrasena' => UsuarioFactory::CLAVE_PRUEBA]);

        $respuesta->assertSessionHasErrors('correo');
        $this->assertStringContainsString('15 minutos', session('errors')->first('correo'));
        $this->assertGuest();
        $this->assertTrue(Bitacora::where('accion', 'inicio_sesion_bloqueado')->exists());
    }

    /** CP-AUT-08 */
    public function test_cierre_de_sesion_invalida_la_sesion(): void
    {
        $this->actingAs($this->aspirante())->post(route('logout'))->assertRedirect(route('inicio'));
        $this->assertGuest();
    }

    /** CP-AUT-09 */
    public function test_bitacora_de_acceso_no_almacena_correo_ni_contrasena(): void
    {
        $usuario = $this->aspirante();
        $this->post(route('login.store'), ['correo' => $usuario->correo, 'contrasena' => 'Incorrecta123']);
        $this->post(route('login.store'), ['correo' => $usuario->correo, 'contrasena' => UsuarioFactory::CLAVE_PRUEBA]);

        $contenido = Bitacora::all()->toJson();
        $this->assertStringNotContainsString($usuario->correo, $contenido);
        $this->assertStringNotContainsString(UsuarioFactory::CLAVE_PRUEBA, $contenido);
        $this->assertTrue(Bitacora::where('accion', 'inicio_sesion')->where('id_usuario', $usuario->id_usuario)->exists());
        $this->assertMatchesRegularExpression('/\.0$/', (string) Bitacora::where('accion', 'inicio_sesion')->value('ip_anonimizada'));
    }

    /** CP-AUT-10 */
    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $usuario = Usuario::factory()->create();
        $usuario->forceFill(['activo' => false])->save();

        $this->post(route('login.store'), ['correo' => $usuario->correo, 'contrasena' => UsuarioFactory::CLAVE_PRUEBA])->assertSessionHasErrors('correo');
        $this->assertGuest();
    }
}
