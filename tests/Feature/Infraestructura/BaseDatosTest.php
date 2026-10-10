<?php

namespace Tests\Feature\Infraestructura;

use App\Models\AreaCompetencia;
use App\Models\Diagnostico;
use App\Models\ModuloContenido;
use App\Models\Pregunta;
use App\Models\RespuestaDiagnostico;
use App\Models\Usuario;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-BD-01 a CP-BD-05. Objetivo específico d): persistencia e integridad del modelo de datos. */
class BaseDatosTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    /** CP-BD-01 */
    public function test_las_migraciones_crean_las_tablas_del_modelo(): void
    {
        $tablas = [
            // Siete tablas del modelo original (Capítulo IV).
            'usuarios', 'diagnosticos', 'perfiles_competencia', 'rutas_aprendizaje', 'modulos_contenido', 'evaluaciones', 'retroalimentaciones',
            // Tablas adicionales justificadas en el Capítulo V.
            'areas_competencia', 'procesos_seleccion', 'postulaciones', 'historial_estados', 'recursos_aprendizaje', 'preguntas',
            'opciones_respuesta', 'respuestas_diagnostico', 'ruta_modulos', 'progreso_recursos', 'valoraciones_modulo',
            'configuraciones_prompt', 'bitacora', 'bitacora_ia',
        ];

        foreach ($tablas as $tabla) {
            $this->assertTrue(Schema::hasTable($tabla), "Falta la tabla {$tabla}");
        }
        $this->assertTrue(Schema::hasColumns('usuarios', ['id_usuario', 'nombre', 'correo', 'contrasena_hash', 'rol', 'fecha_registro']));
    }

    /** CP-BD-02 */
    public function test_el_catalogo_inicial_es_completo_e_idempotente(): void
    {
        $this->seed(CatalogoSeeder::class);
        $this->seed(CatalogoSeeder::class);

        $this->assertSame(5, AreaCompetencia::count());
        $this->assertSame(15, ModuloContenido::count());
        $this->assertSame(60, Pregunta::where('uso', 'diagnostico')->count());
        $this->assertSame(60, Pregunta::where('uso', 'evaluacion_modulo')->count());
        foreach (Pregunta::withCount(['opciones as correctas' => fn ($q) => $q->where('es_correcta', true)])->get() as $pregunta) {
            $this->assertSame(1, (int) $pregunta->correctas, "La pregunta {$pregunta->id_pregunta} debe tener una sola opción correcta.");
        }
    }

    /** CP-BD-03 */
    public function test_eliminar_un_usuario_elimina_en_cascada_sus_diagnosticos(): void
    {
        $this->sembrarCatalogo();
        $usuario = $this->aspirante();
        $this->completarDiagnostico($usuario, false);

        $usuario->delete();

        $this->assertSame(0, Diagnostico::count());
        $this->assertSame(0, RespuestaDiagnostico::count());
    }

    /** CP-BD-04 */
    public function test_no_permite_eliminar_un_modulo_con_recursos(): void
    {
        $this->sembrarCatalogo();

        $this->expectException(QueryException::class);
        ModuloContenido::firstOrFail()->delete();
    }

    /** CP-BD-05 */
    public function test_el_correo_es_unico(): void
    {
        Usuario::factory()->create(['correo' => 'unico@example.com']);

        $this->expectException(QueryException::class);
        Usuario::factory()->create(['correo' => 'unico@example.com']);
    }
}
