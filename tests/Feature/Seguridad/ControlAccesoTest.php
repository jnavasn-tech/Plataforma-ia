<?php

namespace Tests\Feature\Seguridad;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-ACC-01 a CP-ACC-04. RF-01, RF-08, RNF-03: autorización por rol y por propiedad del recurso. */
class ControlAccesoTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    /** CP-ACC-01 */
    public function test_invitado_es_redirigido_al_inicio_de_sesion(): void
    {
        foreach (['aspirante.panel', 'aspirante.ruta', 'admin.panel', 'admin.reportes.index', 'perfil.edit'] as $ruta) {
            $this->get(route($ruta))->assertRedirect(route('login'));
        }
    }

    /** CP-ACC-02 */
    public function test_aspirante_no_accede_a_funciones_administrativas(): void
    {
        $aspirante = $this->aspirante();

        foreach (['admin.panel', 'admin.aspirantes.index', 'admin.recursos.index', 'admin.preguntas.index', 'admin.reportes.index', 'admin.prompts.index', 'admin.bitacora.index'] as $ruta) {
            $this->actingAs($aspirante)->get(route($ruta))->assertForbidden();
        }
    }

    /** CP-ACC-03 */
    public function test_administrador_no_utiliza_las_funciones_del_aspirante(): void
    {
        $this->actingAs($this->administrador())->get(route('aspirante.panel'))->assertForbidden();
    }

    /** CP-ACC-04 */
    public function test_aspirante_no_accede_al_diagnostico_ni_a_los_modulos_de_otro(): void
    {
        $this->sembrarCatalogo();
        $propietario = $this->aspirante();
        $diagnostico = $this->completarDiagnostico($propietario, false);
        $rutaModulo = $diagnostico->perfil->ruta->modulos->first();
        $intruso = $this->aspirante();

        $this->actingAs($intruso)->get(route('aspirante.resultados', $diagnostico))->assertForbidden();
        $this->actingAs($intruso)->get(route('aspirante.diagnostico.mostrar', $diagnostico))->assertForbidden();
        $this->actingAs($intruso)->get(route('aspirante.modulo', $rutaModulo))->assertForbidden();
        $this->actingAs($intruso)->post(route('aspirante.evaluacion.store', $rutaModulo), ['respuestas' => [1 => 1]])->assertForbidden();
    }
}
