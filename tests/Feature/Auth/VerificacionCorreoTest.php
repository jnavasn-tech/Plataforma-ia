<?php

namespace Tests\Feature\Auth;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** CP-AUT-14 a CP-AUT-15. CU-01 (pasos 5 y 6: confirmación y activación de la cuenta). */
class VerificacionCorreoTest extends TestCase
{
    use RefreshDatabase;

    /** CP-AUT-14 */
    public function test_usuario_sin_verificar_no_accede_al_diagnostico(): void
    {
        $usuario = Usuario::factory()->sinVerificar()->create();

        $this->actingAs($usuario)->get(route('aspirante.panel'))->assertRedirect(route('verification.notice'));
    }

    /** CP-AUT-15 */
    public function test_enlace_firmado_verifica_el_correo(): void
    {
        $usuario = Usuario::factory()->sinVerificar()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $usuario->id_usuario,
            'hash' => sha1($usuario->correo),
        ]);

        $this->actingAs($usuario)->get($url)->assertRedirect(route('panel'));
        $this->assertTrue($usuario->fresh()->hasVerifiedEmail());
    }
}
