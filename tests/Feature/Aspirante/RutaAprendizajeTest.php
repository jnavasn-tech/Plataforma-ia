<?php

namespace Tests\Feature\Aspirante;

use App\Models\RecursoAprendizaje;
use App\Models\Usuario;
use App\Notifications\RecursoNoDisponibleNotificacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-RUT-01 a CP-RUT-05. RF-04, RF-05, CU-03 (resultado) y CU-04 (flujo principal y recurso no disponible). */
class RutaAprendizajeTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    private Usuario $aspirante;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCatalogo();
        $this->aspirante = $this->aspirante();
    }

    /** CP-RUT-01 */
    public function test_la_ruta_prioriza_las_areas_con_mayor_brecha(): void
    {
        // Acierta todo excepto en Ciberseguridad: SEG queda en nivel básico y debe encabezar la ruta.
        $ruta = $this->completarDiagnostico($this->aspirante, fn (int $orden, string $area) => $area !== 'SEG')->perfil->ruta;

        $primero = $ruta->modulos->first()->modulo;
        $this->assertSame('SEG', $primero->area->codigo);
        $this->assertSame('alta', $ruta->modulos->first()->prioridad->value);

        $this->actingAs($this->aspirante)->get(route('aspirante.ruta'))
            ->assertOk()->assertSee($primero->nombre)->assertSee('Bloqueado hasta concluir el módulo anterior');
    }

    /** CP-RUT-02 */
    public function test_los_modulos_se_desbloquean_de_forma_secuencial(): void
    {
        $ruta = $this->completarDiagnostico($this->aspirante, false)->perfil->ruta;
        [$primero, $segundo] = [$ruta->modulos[0], $ruta->modulos[1]];

        $this->actingAs($this->aspirante)->get(route('aspirante.modulo', $primero))->assertOk();
        $this->get(route('aspirante.modulo', $segundo))->assertRedirect(route('aspirante.ruta'));
    }

    /** CP-RUT-03 */
    public function test_completar_los_recursos_habilita_la_evaluacion(): void
    {
        $rutaModulo = $this->completarDiagnostico($this->aspirante, false)->perfil->ruta->modulos->first();

        $this->actingAs($this->aspirante)->get(route('aspirante.evaluacion.create', $rutaModulo))->assertRedirect(route('aspirante.modulo', $rutaModulo));

        $recurso = $rutaModulo->modulo->recursosActivos()->first();
        $this->get(route('aspirante.recurso', [$rutaModulo, $recurso]))->assertOk()->assertSee($recurso->titulo);
        $this->assertDatabaseHas('progreso_recursos', ['id_ruta_modulo' => $rutaModulo->id_ruta_modulo, 'id_recurso' => $recurso->id_recurso, 'completado_en' => null]);
        $this->assertSame('en_curso', $rutaModulo->fresh()->estado->value);

        $this->completarRecursos($this->aspirante, $rutaModulo);

        $this->assertSame(66.67, $rutaModulo->fresh()->porcentaje_avance);
        $this->get(route('aspirante.evaluacion.create', $rutaModulo))->assertOk()->assertSee('Intento 1 de 3');
    }

    /** CP-RUT-04 */
    public function test_reportar_recurso_no_disponible_alerta_y_ofrece_alternativas(): void
    {
        Notification::fake();
        $admin = $this->administrador();
        $rutaModulo = $this->completarDiagnostico($this->aspirante, false)->perfil->ruta->modulos->first();
        $recurso = RecursoAprendizaje::create([
            'id_modulo' => $rutaModulo->id_modulo, 'titulo' => 'Video externo de ejemplo', 'tipo' => 'video',
            'url' => 'https://recursos.example.org/video-1', 'estado' => 'activo',
        ]);

        $this->actingAs($this->aspirante)->post(route('aspirante.recurso.reportar', [$rutaModulo, $recurso]))
            ->assertRedirect(route('aspirante.recurso', [$rutaModulo, $recurso]))
            ->assertSessionHas('alternativas');

        $this->assertCount(2, session('alternativas'));
        $this->assertDatabaseMissing('progreso_recursos', ['id_recurso' => $recurso->id_recurso, 'reportado_no_disponible_en' => null]);
        Notification::assertSentTo($admin, RecursoNoDisponibleNotificacion::class);
    }

    /** CP-RUT-05 */
    public function test_un_recurso_de_otro_modulo_no_es_accesible(): void
    {
        $modulos = $this->completarDiagnostico($this->aspirante, false)->perfil->ruta->modulos;
        $ajeno = $modulos[1]->modulo->recursosActivos()->first();

        $this->actingAs($this->aspirante)->get(route('aspirante.recurso', [$modulos[0], $ajeno]))->assertNotFound();
    }
}
