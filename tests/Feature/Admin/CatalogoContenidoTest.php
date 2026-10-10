<?php

namespace Tests\Feature\Admin;

use App\Enums\EstadoRecurso;
use App\Models\ModuloContenido;
use App\Models\OpcionRespuesta;
use App\Models\Pregunta;
use App\Models\RecursoAprendizaje;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-CAT-01 a CP-CAT-08. RF-05, CU-07 (catálogo de recursos) y gestión del banco de preguntas. */
class CatalogoContenidoTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    private Usuario $admin;

    private ModuloContenido $modulo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCatalogo();
        $this->admin = $this->administrador();
        $this->modulo = ModuloContenido::where('codigo', 'OFI-B')->firstOrFail();
    }

    private function recurso(array $cambios = []): array
    {
        return array_merge([
            'id_modulo' => $this->modulo->id_modulo, 'titulo' => 'Video: márgenes y orientación', 'tipo' => 'video',
            'url' => 'https://recursos.example.org/ofimatica/margenes', 'duracion_minutos' => 8,
        ], $cambios);
    }

    /** CP-CAT-01 */
    public function test_registra_un_recurso_con_url_accesible(): void
    {
        Http::fake(['recursos.example.org/*' => Http::response('', 200)]);

        $this->actingAs($this->admin)->post(route('admin.recursos.store'), $this->recurso())->assertRedirect();

        $recurso = RecursoAprendizaje::where('titulo', 'Video: márgenes y orientación')->firstOrFail();
        $this->assertSame(EstadoRecurso::Activo, $recurso->estado);
        $this->assertSame($this->admin->id_usuario, (int) $recurso->creado_por);
    }

    /** CP-CAT-02 */
    public function test_url_inaccesible_requiere_confirmar_estado_pendiente(): void
    {
        Http::fake(['recursos.example.org/*' => Http::response('', 404)]);

        $this->actingAs($this->admin)->post(route('admin.recursos.store'), $this->recurso())
            ->assertSessionHasErrors(['url' => 'La URL no es accesible. Verifique el enlace antes de guardar.'])
            ->assertSessionHas('ofrecer_pendiente');
        $this->assertDatabaseMissing('recursos_aprendizaje', ['titulo' => 'Video: márgenes y orientación']);

        $this->post(route('admin.recursos.store'), $this->recurso(['guardar_pendiente' => '1']))->assertRedirect();
        $this->assertSame(EstadoRecurso::PendienteVerificacion, RecursoAprendizaje::where('titulo', 'Video: márgenes y orientación')->first()->estado);
    }

    /** CP-CAT-03 */
    public function test_url_duplicada_muestra_el_recurso_existente(): void
    {
        Http::fake(['recursos.example.org/*' => Http::response('', 200)]);
        $this->actingAs($this->admin)->post(route('admin.recursos.store'), $this->recurso());

        $this->post(route('admin.recursos.store'), $this->recurso(['titulo' => 'Otro título']))
            ->assertSessionHasErrors(['url' => 'Ya existe un recurso con esta URL: Video: márgenes y orientación. ¿Desea editarlo en su lugar?']);
    }

    /** CP-CAT-04 */
    public function test_lectura_interna_requiere_contenido_si_no_hay_url(): void
    {
        $this->actingAs($this->admin)->post(route('admin.recursos.store'), $this->recurso(['url' => null, 'tipo' => 'lectura']))
            ->assertSessionHasErrors('contenido');
    }

    /** CP-CAT-05 */
    public function test_la_baja_logica_excluye_el_recurso_de_nuevas_rutas(): void
    {
        $recurso = $this->modulo->recursosActivos()->first();

        $this->actingAs($this->admin)->patch(route('admin.recursos.estado', $recurso), ['estado' => 'inactivo'])->assertSessionHas('estado');

        $this->assertSame(EstadoRecurso::Inactivo, $recurso->fresh()->estado);
        $this->assertFalse($this->modulo->recursosActivos()->whereKey($recurso->id_recurso)->exists());
    }

    /** CP-CAT-06 */
    public function test_crea_una_pregunta_con_una_unica_opcion_correcta(): void
    {
        $this->actingAs($this->admin)->post(route('admin.preguntas.store'), [
            'id_area' => $this->modulo->id_area, 'uso' => 'diagnostico', 'dificultad' => 2,
            'enunciado' => '¿Qué función calcula la media aritmética?', 'opciones' => ['PROMEDIO', 'SUMA', '', 'MAX'], 'correcta' => 0,
        ])->assertRedirect(route('admin.preguntas.index'));

        $pregunta = Pregunta::where('enunciado', '¿Qué función calcula la media aritmética?')->firstOrFail();
        $this->assertSame(3, $pregunta->opciones()->count());
        $this->assertSame('PROMEDIO', OpcionRespuesta::where('id_pregunta', $pregunta->id_pregunta)->where('es_correcta', true)->value('texto'));
    }

    /** CP-CAT-07 */
    public function test_rechaza_preguntas_con_opcion_correcta_invalida_o_repetidas(): void
    {
        $base = ['id_area' => $this->modulo->id_area, 'uso' => 'diagnostico', 'dificultad' => 1, 'enunciado' => 'Enunciado de prueba válido'];

        $this->actingAs($this->admin)->post(route('admin.preguntas.store'), $base + ['opciones' => ['A', 'B'], 'correcta' => 5])->assertSessionHasErrors('correcta');
        $this->post(route('admin.preguntas.store'), $base + ['opciones' => ['A', 'a'], 'correcta' => 0])->assertSessionHasErrors('opciones');
        $this->post(route('admin.preguntas.store'), array_merge($base, ['uso' => 'evaluacion_modulo', 'opciones' => ['A', 'B'], 'correcta' => 0]))->assertSessionHasErrors('id_modulo');
    }

    /** CP-CAT-08 */
    public function test_editar_una_pregunta_utilizada_crea_una_nueva_version(): void
    {
        $this->completarDiagnostico($this->aspirante(), true);
        $usada = Pregunta::whereHas('area', fn ($q) => $q->where('codigo', 'OFI'))->where('uso', 'diagnostico')
            ->whereIn('id_pregunta', \App\Models\RespuestaDiagnostico::pluck('id_pregunta'))->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.preguntas.update', $usada), [
            'id_area' => $usada->id_area, 'uso' => 'diagnostico', 'dificultad' => $usada->dificultad,
            'enunciado' => $usada->enunciado.' (revisada)', 'opciones' => ['Correcta', 'Incorrecta'], 'correcta' => 0,
        ])->assertRedirect();

        $this->assertFalse($usada->fresh()->activo);
        $this->assertDatabaseHas('preguntas', ['enunciado' => $usada->enunciado.' (revisada)', 'version' => 2, 'activo' => true]);
        $this->assertSame(4, $usada->opciones()->count(), 'Las opciones originales se conservan para las respuestas históricas.');
    }
}
