<?php

namespace Tests\Feature\Aspirante;

use App\Enums\NivelCompetencia;
use App\Enums\OrigenGeneracion;
use App\Notifications\PerfilMaximoNotificacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-PER-01 a CP-PER-04. RF-03, CU-02 (resultados) y CU-03 (flujo alterno de perfil máximo). */
class PerfilCompetenciasTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCatalogo();
    }

    /** CP-PER-01 */
    public function test_todas_correctas_genera_perfil_avanzado_y_ruta_avanzada_opcional(): void
    {
        Notification::fake();
        $admin = $this->administrador();

        $perfil = $this->completarDiagnostico($this->aspirante(), true)->perfil;

        $this->assertSame(NivelCompetencia::Avanzado, $perfil->nivel_general);
        $this->assertSame(100.0, $perfil->puntaje_global);
        $this->assertTrue($perfil->ruta->es_ruta_avanzada);
        Notification::assertSentTo($admin, PerfilMaximoNotificacion::class);
    }

    /** CP-PER-02 */
    public function test_todas_incorrectas_genera_perfil_basico_con_ruta_de_prioridad_alta(): void
    {
        $perfil = $this->completarDiagnostico($this->aspirante(), false)->perfil;

        $this->assertSame(NivelCompetencia::Basico, $perfil->nivel_general);
        $this->assertSame(0.0, $perfil->puntaje_global);
        $this->assertFalse($perfil->ruta->es_ruta_avanzada);
        $this->assertTrue($perfil->ruta->modulos->every(fn ($m) => $m->prioridad->value === 'alta'));
    }

    /** CP-PER-03 */
    public function test_los_puntajes_por_area_quedan_persistidos(): void
    {
        // Acierta solo en Ofimática.
        $perfil = $this->completarDiagnostico($this->aspirante(), fn (int $orden, string $area) => $area === 'OFI')->perfil;

        $puntajes = $perfil->puntajesPorCodigo();
        $this->assertSame(['OFI', 'RED', 'SEG', 'COL', 'PRO'], array_keys($puntajes));
        $this->assertSame(100.0, $puntajes['OFI']);
        $this->assertSame(0.0, $puntajes['RED']);
        $this->assertSame(20.0, $perfil->puntaje_global);
    }

    /** CP-PER-04 */
    public function test_sin_ia_la_interpretacion_y_la_ruta_se_generan_por_reglas(): void
    {
        $perfil = $this->completarDiagnostico($this->aspirante(), false)->perfil;

        $this->assertSame(OrigenGeneracion::Reglas, $perfil->origen_interpretacion);
        $this->assertNotEmpty($perfil->resumen);
        $this->assertSame(OrigenGeneracion::Reglas, $perfil->ruta->origen);
        $this->assertDatabaseHas('bitacora_ia', ['tipo' => 'ruta', 'estado' => 'deshabilitada']);
    }
}
