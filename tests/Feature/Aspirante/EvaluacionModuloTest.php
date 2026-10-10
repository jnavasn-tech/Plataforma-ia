<?php

namespace Tests\Feature\Aspirante;

use App\Enums\EstadoRuta;
use App\Enums\OrigenGeneracion;
use App\Models\Evaluacion;
use App\Models\RutaAprendizaje;
use App\Models\Usuario;
use App\Notifications\RutaCompletadaNotificacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-EVA-01 a CP-EVA-07. RF-06, RF-07, CU-04 (actividad de verificación) y postest. */
class EvaluacionModuloTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    private Usuario $aspirante;

    private RutaAprendizaje $ruta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCatalogo();
        $this->aspirante = $this->aspirante();
        $this->ruta = $this->completarDiagnostico($this->aspirante, false)->perfil->ruta;
    }

    private function evaluar(int $indice, bool $correctas)
    {
        $rutaModulo = $this->ruta->modulos[$indice]->fresh();

        return $this->actingAs($this->aspirante)->post(route('aspirante.evaluacion.store', $rutaModulo), ['respuestas' => $this->respuestasEvaluacion($rutaModulo, $correctas)]);
    }

    /** CP-EVA-01 */
    public function test_evaluacion_aprobada_concluye_el_modulo_y_genera_retroalimentacion(): void
    {
        $rutaModulo = $this->ruta->modulos[0];
        $this->completarRecursos($this->aspirante, $rutaModulo);

        $this->evaluar(0, true)->assertRedirect();

        $evaluacion = Evaluacion::firstOrFail();
        $this->assertSame(100.0, $evaluacion->calificacion);
        $this->assertTrue($evaluacion->aprobado);
        $this->assertSame('completado', $rutaModulo->fresh()->estado->value);
        $this->assertSame(OrigenGeneracion::Reglas, $evaluacion->retroalimentacion->origen);
        $this->get(route('aspirante.evaluacion.show', $evaluacion))->assertOk()->assertSee('Excelente desempeño')->assertSee('Aprobado');
        $this->get(route('aspirante.modulo', $this->ruta->modulos[1]))->assertOk();
    }

    /** CP-EVA-02 */
    public function test_evaluacion_reprobada_permite_reintentar(): void
    {
        $this->completarRecursos($this->aspirante, $this->ruta->modulos[0]);

        $this->evaluar(0, false);

        $evaluacion = Evaluacion::firstOrFail();
        $this->assertSame(0.0, $evaluacion->calificacion);
        $this->assertFalse($evaluacion->aprobado);
        $this->get(route('aspirante.evaluacion.show', $evaluacion))->assertSee('Le recomendamos revisar el recurso antes de continuar')->assertSee('Revisar recursos y reintentar');
        $this->get(route('aspirante.evaluacion.create', $this->ruta->modulos[0]))->assertOk()->assertSee('Intento 2 de 3');
    }

    /** CP-EVA-03 */
    public function test_tres_intentos_fallidos_desbloquean_el_siguiente_modulo(): void
    {
        $this->completarRecursos($this->aspirante, $this->ruta->modulos[0]);

        for ($i = 0; $i < 3; $i++) {
            $this->evaluar(0, false);
        }
        $this->evaluar(0, false)->assertSessionHasErrors('evaluacion');

        $this->assertSame(3, Evaluacion::count());
        $this->assertSame('completado', $this->ruta->modulos[0]->fresh()->estado->value);
        $this->get(route('aspirante.modulo', $this->ruta->modulos[1]))->assertOk();
    }

    /** CP-EVA-04 */
    public function test_respuestas_incompletas_son_rechazadas(): void
    {
        $rutaModulo = $this->ruta->modulos[0];
        $this->completarRecursos($this->aspirante, $rutaModulo);
        $respuestas = $this->respuestasEvaluacion($rutaModulo, true);
        array_pop($respuestas);

        $this->actingAs($this->aspirante)->post(route('aspirante.evaluacion.store', $rutaModulo), ['respuestas' => $respuestas])->assertSessionHasErrors();
        $this->assertSame(0, Evaluacion::count());
    }

    /** CP-EVA-05 */
    public function test_retroalimentacion_generada_por_ia_cuando_esta_disponible(): void
    {
        $this->habilitarIA();
        Http::fake(['api.openai.com/*' => Http::response($this->respuestaOpenAI([
            'mensaje' => 'Buen trabajo: domina los fundamentos del módulo.',
            'sugerencias' => ['Practique con un documento real de su entorno.'],
            'siguiente_paso' => 'Continúe con el siguiente módulo.',
        ]))]);
        $this->completarRecursos($this->aspirante, $this->ruta->modulos[0]);

        $this->evaluar(0, true);

        $retro = Evaluacion::firstOrFail()->retroalimentacion;
        $this->assertSame(OrigenGeneracion::IA, $retro->origen);
        $this->assertSame('retroalimentacion-v1', $retro->version_prompt);
        $this->assertStringContainsString('Buen trabajo', $retro->texto_generado);
    }

    /** CP-EVA-06 */
    public function test_completar_todos_los_modulos_completa_la_ruta_y_habilita_el_postest(): void
    {
        Notification::fake();
        $admin = $this->administrador();

        foreach ($this->ruta->modulos as $i => $rutaModulo) {
            $this->completarRecursos($this->aspirante, $rutaModulo->fresh());
            $this->evaluar($i, true);
        }

        $this->assertSame(EstadoRuta::Completada, $this->ruta->fresh()->estado);
        Notification::assertSentTo($admin, RutaCompletadaNotificacion::class);
        $this->actingAs($this->aspirante)->get(route('aspirante.panel'))->assertSee('Iniciar diagnóstico final');
        $this->post(route('aspirante.diagnostico.iniciar', 'postest'))->assertRedirect();
        $this->assertDatabaseHas('diagnosticos', ['id_usuario' => $this->aspirante->id_usuario, 'tipo' => 'postest']);
    }

    /** CP-EVA-07 */
    public function test_la_valoracion_del_modulo_se_registra_al_concluirlo(): void
    {
        $rutaModulo = $this->ruta->modulos[0];
        $this->actingAs($this->aspirante)->post(route('aspirante.valoracion.store', $rutaModulo), ['puntuacion' => 5])->assertStatus(422);

        $this->completarRecursos($this->aspirante, $rutaModulo);
        $this->evaluar(0, true);
        $this->post(route('aspirante.valoracion.store', $rutaModulo), ['puntuacion' => 4, 'comentario' => 'Muy útil'])->assertRedirect();

        $this->assertDatabaseHas('valoraciones_modulo', ['id_ruta_modulo' => $rutaModulo->id_ruta_modulo, 'puntuacion' => 4]);
    }
}
