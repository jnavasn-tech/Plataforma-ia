<?php

namespace Tests\Feature\Admin;

use App\Models\BitacoraIA;
use App\Models\ConfiguracionPrompt;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-PRM-01, CP-PRM-02 y CP-MET-01 a CP-MET-03. RF-08 (prompts, métricas y bitácora; CU-09 propuesto). */
class PromptsYMetricasTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCatalogo();
        $this->admin = $this->administrador();
    }

    /** CP-PRM-01 */
    public function test_nueva_version_activada_deja_una_sola_version_activa(): void
    {
        $contenido = str_repeat('Instrucción institucional de prueba. ', 5);

        $this->actingAs($this->admin)->post(route('admin.prompts.store'), [
            'clave' => 'ruta', 'contenido' => $contenido, 'descripcion_cambio' => 'Ajuste de tono', 'activar' => '1',
        ])->assertRedirect(route('admin.prompts.index'));

        $this->assertSame(1, ConfiguracionPrompt::where('clave', 'ruta')->where('activo', true)->count());
        $this->assertSame(2, ConfiguracionPrompt::activo(\App\Enums\ClavePrompt::Ruta)->version);
        $this->assertDatabaseHas('bitacora', ['accion' => 'prompt_activado', 'id_usuario' => $this->admin->id_usuario]);

        $v1 = ConfiguracionPrompt::where('clave', 'ruta')->where('version', 1)->firstOrFail();
        $this->patch(route('admin.prompts.activar', $v1))->assertSessionHas('estado');
        $this->assertSame(1, ConfiguracionPrompt::activo(\App\Enums\ClavePrompt::Ruta)->version);
    }

    /** CP-PRM-02 */
    public function test_valida_el_contenido_minimo_del_prompt(): void
    {
        $this->actingAs($this->admin)->post(route('admin.prompts.store'), ['clave' => 'ruta', 'contenido' => 'corto', 'descripcion_cambio' => 'x'])
            ->assertSessionHasErrors('contenido');
        $this->post(route('admin.prompts.store'), ['clave' => 'otra', 'contenido' => str_repeat('a', 60), 'descripcion_cambio' => 'x'])
            ->assertSessionHasErrors('clave');
    }

    /** CP-MET-01 */
    public function test_panel_sin_datos_no_inventa_indicadores(): void
    {
        $this->actingAs($this->admin)->get(route('admin.panel'))
            ->assertOk()->assertSee('Sin datos')->assertSee('Banco de preguntas de diagnóstico');
    }

    /** CP-MET-02 */
    public function test_metricas_se_calculan_a_partir_de_los_registros(): void
    {
        $this->completarDiagnostico($this->aspirante(), true);
        $this->completarDiagnostico($this->aspirante(), false);

        $metricas = app(\App\Services\Metricas\ServicioMetricas::class)->resumen();

        $this->assertSame(2, $metricas['diagnosticos']['pretest_completados']);
        $this->assertSame(2, $metricas['rutas']['generadas']);
        $this->assertSame(0.0, $metricas['rutas']['tasa_retencion']);
        $this->assertSame(60, $metricas['tiempo_reactivo']['n']);
        $this->assertNull($metricas['precision']['porcentaje']);
        $this->assertNull($metricas['mejora']['promedio_pp']);
        $this->assertSame(BitacoraIA::count(), $metricas['ia']['total']);
    }

    /** CP-MET-03 */
    public function test_bitacora_muestra_actividad_y_solicitudes_de_ia(): void
    {
        $this->completarDiagnostico($this->aspirante(), false);

        $this->actingAs($this->admin)->get(route('admin.bitacora.index'))->assertOk()->assertSee('diagnostico_completado');
        $this->get(route('admin.bitacora.index', ['vista' => 'ia']))->assertOk()->assertSee('deshabilitada');
    }
}
