<?php

namespace Tests\Feature\Aspirante;

use App\Enums\EstadoDiagnostico;
use App\Models\Diagnostico;
use App\Models\OpcionRespuesta;
use App\Models\Pregunta;
use App\Models\RespuestaDiagnostico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-DIA-01 a CP-DIA-10. RF-02, RF-03, RNF-02, CU-02 (flujo principal y alternos). */
class DiagnosticoTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCatalogo();
    }

    /** CP-DIA-01 */
    public function test_instrucciones_muestran_cinco_areas_y_treinta_preguntas(): void
    {
        $this->actingAs($this->aspirante())->get(route('aspirante.diagnostico.instrucciones', 'pretest'))
            ->assertOk()
            ->assertSee('30 preguntas')
            ->assertSee('Ofimática')->assertSee('Redes e Internet')->assertSee('Ciberseguridad')
            ->assertSee('Colaboración digital')->assertSee('Productividad digital');
    }

    /** CP-DIA-02 */
    public function test_flujo_completo_por_http_genera_perfil_y_ruta(): void
    {
        $aspirante = $this->aspirante();
        $this->actingAs($aspirante)->post(route('aspirante.diagnostico.iniciar', 'pretest'))->assertRedirect();
        $diagnostico = Diagnostico::firstOrFail();

        for ($i = 1; $i <= 30; $i++) {
            $this->get(route('aspirante.diagnostico.mostrar', $diagnostico))->assertOk()->assertSee("Pregunta {$i} de 30");
            $item = RespuestaDiagnostico::where('id_diagnostico', $diagnostico->id_diagnostico)->whereNull('respondida_en')->firstOrFail();
            $opcion = OpcionRespuesta::where('id_pregunta', $item->id_pregunta)->where('es_correcta', $i % 2 === 0)->firstOrFail();
            $this->post(route('aspirante.diagnostico.responder', $diagnostico), ['id_respuesta' => $item->id_respuesta, 'id_opcion' => $opcion->id_opcion]);
        }

        $diagnostico->refresh();
        $this->assertSame(EstadoDiagnostico::Completado, $diagnostico->estado);
        $this->assertSame(30, $diagnostico->respuestas()->count());
        $this->assertCount(5, $diagnostico->perfil->areas_evaluadas_json);
        foreach ($diagnostico->perfil->areas_evaluadas_json as $area) {
            $this->assertSame(6, $area['respondidas']);
            $this->assertGreaterThanOrEqual(0, $area['puntaje']);
            $this->assertLessThanOrEqual(100, $area['puntaje']);
        }
        $this->assertNotNull($diagnostico->perfil->ruta, 'La ruta se genera automáticamente (CU-03) para el 100 % de los perfiles.');
        $this->get(route('aspirante.resultados', $diagnostico))->assertOk()->assertSee('Gráfico de resultados')->assertSee('<svg', false);
    }

    /** CP-DIA-03 */
    public function test_no_permite_avanzar_sin_seleccionar_respuesta(): void
    {
        $aspirante = $this->aspirante();
        $this->actingAs($aspirante)->post(route('aspirante.diagnostico.iniciar', 'pretest'));
        $diagnostico = Diagnostico::firstOrFail();
        $this->get(route('aspirante.diagnostico.mostrar', $diagnostico));
        $item = RespuestaDiagnostico::firstOrFail();

        $this->post(route('aspirante.diagnostico.responder', $diagnostico), ['id_respuesta' => $item->id_respuesta])
            ->assertSessionHasErrors(['id_opcion' => 'Por favor seleccione una respuesta antes de continuar.']);
        $this->assertNull($item->fresh()->respondida_en);
    }

    /** CP-DIA-04 */
    public function test_rechaza_una_opcion_que_no_pertenece_a_la_pregunta(): void
    {
        $aspirante = $this->aspirante();
        $this->actingAs($aspirante)->post(route('aspirante.diagnostico.iniciar', 'pretest'));
        $diagnostico = Diagnostico::firstOrFail();
        $this->get(route('aspirante.diagnostico.mostrar', $diagnostico));
        $item = RespuestaDiagnostico::firstOrFail();
        $ajena = OpcionRespuesta::where('id_pregunta', '!=', $item->id_pregunta)->firstOrFail();

        $this->post(route('aspirante.diagnostico.responder', $diagnostico), ['id_respuesta' => $item->id_respuesta, 'id_opcion' => $ajena->id_opcion])
            ->assertSessionHasErrors('id_opcion');
        $this->assertNull($item->fresh()->id_opcion);
    }

    /** CP-DIA-05 */
    public function test_reanuda_desde_el_item_pendiente_tras_cerrar_sesion(): void
    {
        $aspirante = $this->aspirante();
        $this->actingAs($aspirante)->post(route('aspirante.diagnostico.iniciar', 'pretest'));
        $diagnostico = Diagnostico::firstOrFail();
        $this->get(route('aspirante.diagnostico.mostrar', $diagnostico));
        $pendiente = RespuestaDiagnostico::firstOrFail();

        $this->post(route('logout'));
        $this->actingAs($aspirante)->post(route('aspirante.diagnostico.iniciar', 'pretest'))->assertRedirect(route('aspirante.diagnostico.mostrar', $diagnostico));
        $this->get(route('aspirante.diagnostico.mostrar', $diagnostico))->assertSee(e($pendiente->pregunta->enunciado), false);
        $this->assertSame(1, Diagnostico::count());
        $this->assertSame(1, RespuestaDiagnostico::count());
    }

    /** CP-DIA-06 */
    public function test_no_permite_un_segundo_pretest_en_el_mismo_proceso(): void
    {
        $aspirante = $this->aspirante();
        $this->completarDiagnostico($aspirante);

        $this->actingAs($aspirante)->post(route('aspirante.diagnostico.iniciar', 'pretest'))
            ->assertRedirect(route('aspirante.panel'))
            ->assertSessionHas('aviso');
        $this->assertSame(1, Diagnostico::count());
    }

    /** CP-DIA-07 */
    public function test_diagnostico_inactivo_se_marca_abandonado_y_permite_iniciar_otro(): void
    {
        $aspirante = $this->aspirante();
        $this->actingAs($aspirante)->post(route('aspirante.diagnostico.iniciar', 'pretest'));
        $anterior = Diagnostico::firstOrFail();
        $anterior->update(['ultima_actividad_en' => now()->subHours(config('plataforma.diagnostico.horas_inactividad_abandono') + 1)]);

        $this->post(route('aspirante.diagnostico.iniciar', 'pretest'));

        $this->assertSame(EstadoDiagnostico::Abandonado, $anterior->fresh()->estado);
        $this->assertSame(2, Diagnostico::count());
    }

    /** CP-DIA-08 */
    public function test_banco_insuficiente_muestra_aviso_sin_iniciar(): void
    {
        Pregunta::where('uso', 'diagnostico')->whereHas('area', fn ($q) => $q->where('codigo', 'SEG'))->update(['activo' => false]);

        $this->actingAs($this->aspirante())->post(route('aspirante.diagnostico.iniciar', 'pretest'))
            ->assertRedirect(route('aspirante.panel'))
            ->assertSessionHas('aviso');
        $this->assertSame(0, Diagnostico::count());
    }

    /** CP-DIA-09 */
    public function test_registra_tiempos_de_respuesta_y_de_servidor_por_reactivo(): void
    {
        $diagnostico = $this->completarDiagnostico($this->aspirante());

        $this->assertSame(30, RespuestaDiagnostico::where('id_diagnostico', $diagnostico->id_diagnostico)->whereNotNull('tiempo_servidor_ms')->count());
        $this->assertSame(30, RespuestaDiagnostico::whereNotNull('tiempo_respuesta_ms')->count());
    }

    /** CP-DIA-10 */
    public function test_doble_envio_del_mismo_reactivo_no_duplica_respuestas(): void
    {
        $aspirante = $this->aspirante();
        $this->actingAs($aspirante)->post(route('aspirante.diagnostico.iniciar', 'pretest'));
        $diagnostico = Diagnostico::firstOrFail();
        $this->get(route('aspirante.diagnostico.mostrar', $diagnostico));
        $item = RespuestaDiagnostico::firstOrFail();
        $opcion = OpcionRespuesta::where('id_pregunta', $item->id_pregunta)->firstOrFail();

        $this->post(route('aspirante.diagnostico.responder', $diagnostico), ['id_respuesta' => $item->id_respuesta, 'id_opcion' => $opcion->id_opcion]);
        $this->post(route('aspirante.diagnostico.responder', $diagnostico), ['id_respuesta' => $item->id_respuesta, 'id_opcion' => $opcion->id_opcion]);

        $this->assertSame(1, RespuestaDiagnostico::whereNotNull('respondida_en')->count());
        $this->assertSame(2, RespuestaDiagnostico::count());
    }
}
