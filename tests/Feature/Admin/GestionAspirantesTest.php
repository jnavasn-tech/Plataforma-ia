<?php

namespace Tests\Feature\Admin;

use App\Enums\EstadoPostulacion;
use App\Models\Postulacion;
use App\Models\ProcesoSeleccion;
use App\Models\Usuario;
use App\Notifications\CambioEstadoNotificacion;
use App\Notifications\InvitacionDiagnosticoNotificacion;
use App\Notifications\MensajeInternoNotificacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-ASP-01 a CP-ASP-05. RF-08, CU-05 "Gestionar aspirantes". */
class GestionAspirantesTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    private Usuario $admin;

    private ProcesoSeleccion $proceso;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCatalogo();
        $this->admin = $this->administrador();
        $this->proceso = ProcesoSeleccion::vigente();
    }

    private function aspiranteEnProceso(string $estado = 'pendiente'): array
    {
        $aspirante = $this->aspirante();
        $postulacion = Postulacion::create(['id_usuario' => $aspirante->id_usuario, 'id_proceso' => $this->proceso->id_proceso, 'estado' => $estado]);

        return [$aspirante, $postulacion];
    }

    /** CP-ASP-01 */
    public function test_lista_y_filtra_aspirantes_por_estado(): void
    {
        [$pendiente] = $this->aspiranteEnProceso('pendiente');
        [$apto] = $this->aspiranteEnProceso('apto');

        $this->actingAs($this->admin)->get(route('admin.aspirantes.index', ['estado' => 'apto']))
            ->assertOk()->assertSee($apto->nombre)->assertDontSee($pendiente->nombre);
    }

    /** CP-ASP-02 */
    public function test_cambio_de_estado_registra_historial_y_notifica_al_aspirante(): void
    {
        Notification::fake();
        [$aspirante, $postulacion] = $this->aspiranteEnProceso();

        $this->actingAs($this->admin)->put(route('admin.aspirantes.estado', [$aspirante, $postulacion]), ['estado' => 'apto', 'comentario' => 'Completó la ruta'])
            ->assertSessionHas('estado');

        $this->assertSame(EstadoPostulacion::Apto, $postulacion->fresh()->estado);
        $this->assertDatabaseHas('historial_estados', [
            'id_postulacion' => $postulacion->id_postulacion, 'estado_anterior' => 'pendiente', 'estado_nuevo' => 'apto', 'id_admin' => $this->admin->id_usuario,
        ]);
        Notification::assertSentTo($aspirante, CambioEstadoNotificacion::class);
        $this->assertDatabaseHas('bitacora', ['accion' => 'estado_postulacion_actualizado', 'id_usuario' => $this->admin->id_usuario]);
    }

    /** CP-ASP-03 */
    public function test_no_permite_modificar_la_postulacion_de_otro_aspirante(): void
    {
        [$aspirante] = $this->aspiranteEnProceso();
        [, $postulacionAjena] = $this->aspiranteEnProceso();

        $this->actingAs($this->admin)->put(route('admin.aspirantes.estado', [$aspirante, $postulacionAjena]), ['estado' => 'apto'])->assertNotFound();
    }

    /** CP-ASP-04 */
    public function test_aspirante_sin_diagnostico_permite_reenviar_la_invitacion(): void
    {
        Notification::fake();
        [$aspirante] = $this->aspiranteEnProceso();

        $this->actingAs($this->admin)->get(route('admin.aspirantes.show', $aspirante))
            ->assertOk()->assertSee('Este aspirante no ha completado el diagnóstico. Puede reenviar la invitación desde aquí.');
        $this->post(route('admin.aspirantes.invitacion', $aspirante))->assertSessionHas('estado');

        Notification::assertSentTo($aspirante, InvitacionDiagnosticoNotificacion::class);
    }

    /** CP-ASP-05 */
    public function test_registra_el_nivel_de_control_y_envia_mensajes_internos(): void
    {
        Notification::fake();
        [$aspirante] = $this->aspiranteEnProceso();
        $perfil = $this->completarDiagnostico($aspirante, false)->perfil;

        $this->actingAs($this->admin)->get(route('admin.aspirantes.show', $aspirante))->assertOk()->assertSee('<svg', false);
        $this->put(route('admin.perfiles.control', $perfil), ['nivel_control' => 'basico'])->assertSessionHas('estado');
        $this->post(route('admin.aspirantes.mensaje', $aspirante), ['mensaje' => 'Recuerde completar su ruta esta semana.'])->assertSessionHas('estado');

        $this->assertSame('basico', $perfil->fresh()->nivel_control->value);
        Notification::assertSentTo($aspirante, MensajeInternoNotificacion::class);
    }
}
