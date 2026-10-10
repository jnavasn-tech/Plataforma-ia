<?php

namespace Tests\Feature\Auth;

use App\Enums\Rol;
use App\Models\Bitacora;
use App\Models\Postulacion;
use App\Models\Usuario;
use App\Notifications\VerificarCorreoNotificacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-AUT-01 a CP-AUT-04. RF-01, RNF-03, CU-01 (registro). */
class RegistroTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'María José Pérez',
            'correo' => 'maria.prueba@example.com',
            'contrasena' => 'ClaveSegura123',
            'contrasena_confirmation' => 'ClaveSegura123',
            'declara_elegibilidad' => '1',
            'acepta_privacidad' => '1',
        ], $cambios);
    }

    /** CP-AUT-01 */
    public function test_registro_exitoso_crea_aspirante_con_contrasena_cifrada_y_postulacion(): void
    {
        Notification::fake();
        $this->sembrarCatalogo();

        $this->post(route('registro.store'), $this->datos())->assertRedirect(route('verification.notice'));

        $usuario = Usuario::where('correo', 'maria.prueba@example.com')->firstOrFail();
        $this->assertSame(Rol::Aspirante, $usuario->rol);
        $this->assertStringStartsWith('$2y$', $usuario->contrasena_hash);
        $this->assertNotSame('ClaveSegura123', $usuario->contrasena_hash);
        $this->assertNotNull($usuario->consentimiento_privacidad_at);
        $this->assertTrue(Postulacion::where('id_usuario', $usuario->id_usuario)->exists());
        $this->assertAuthenticatedAs($usuario);
        Notification::assertSentTo($usuario, VerificarCorreoNotificacion::class);
        $this->assertTrue(Bitacora::where('accion', 'registro')->where('id_usuario', $usuario->id_usuario)->exists());
    }

    /** CP-AUT-02 */
    public function test_correo_duplicado_muestra_el_mensaje_del_caso_de_uso(): void
    {
        Usuario::factory()->create(['correo' => 'maria.prueba@example.com']);

        $this->from(route('registro'))->post(route('registro.store'), $this->datos(['correo' => 'MARIA.prueba@example.com ']))
            ->assertRedirect(route('registro'))
            ->assertSessionHasErrors(['correo' => 'Este correo ya está registrado. ¿Desea iniciar sesión?']);
        $this->assertSame(1, Usuario::count());
    }

    /** CP-AUT-03 */
    public function test_contrasena_debil_es_rechazada(): void
    {
        foreach (['corta1A', 'sinnumerosAA', 'sinmayuscula1'] as $debil) {
            $this->post(route('registro.store'), $this->datos(['contrasena' => $debil, 'contrasena_confirmation' => $debil]))
                ->assertSessionHasErrors('contrasena');
        }
        $this->assertSame(0, Usuario::count());
    }

    /** CP-AUT-04 */
    public function test_requiere_privacidad_y_consentimiento_de_tutor_para_menores(): void
    {
        $this->post(route('registro.store'), $this->datos(['acepta_privacidad' => null]))->assertSessionHasErrors('acepta_privacidad');
        $this->post(route('registro.store'), $this->datos(['es_menor_edad' => '1']))->assertSessionHasErrors('consentimiento_tutor');

        Notification::fake();
        $this->post(route('registro.store'), $this->datos(['es_menor_edad' => '1', 'consentimiento_tutor' => '1']))->assertSessionHasNoErrors();
        $usuario = Usuario::firstOrFail();
        $this->assertTrue($usuario->es_menor_edad);
        $this->assertNotNull($usuario->consentimiento_tutor_at);
    }
}
