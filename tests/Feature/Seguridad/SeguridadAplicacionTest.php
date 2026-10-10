<?php

namespace Tests\Feature\Seguridad;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Diagnostico;
use App\Models\RecursoAprendizaje;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-SEG-01 a CP-SEG-07. RNF-03 y lista de verificación de seguridad (Anexo U). */
class SeguridadAplicacionTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    /** CP-SEG-01 */
    public function test_las_respuestas_incluyen_cabeceras_de_seguridad(): void
    {
        $this->get(route('inicio'))
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');
    }

    /** CP-SEG-02 */
    public function test_formularios_sin_token_csrf_son_rechazados(): void
    {
        $middleware = new class(app(), app(Encrypter::class)) extends VerifyCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        };
        $solicitud = Request::create('/iniciar-sesion', 'POST', ['correo' => 'a@example.com', 'contrasena' => 'x']);
        $solicitud->setLaravelSession(app('session.store'));

        $this->expectException(TokenMismatchException::class);
        $middleware->handle($solicitud, fn () => response('no debería llegar'));
    }

    /** CP-SEG-03 */
    public function test_el_contenido_ingresado_se_escapa_para_prevenir_xss(): void
    {
        $this->sembrarCatalogo();
        $aspirante = $this->aspirante();
        $rutaModulo = $this->completarDiagnostico($aspirante, false)->perfil->ruta->modulos->first();
        $recurso = $rutaModulo->modulo->recursosActivos()->first();
        $recurso->update(['contenido' => '<script>alert("xss")</script> Texto seguro']);

        $this->actingAs($aspirante)->get(route('aspirante.recurso', [$rutaModulo, $recurso]))
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    /** CP-SEG-04 */
    public function test_entradas_maliciosas_en_filtros_no_alteran_las_consultas(): void
    {
        $this->aspirante();

        $this->actingAs($this->administrador())->get(route('admin.aspirantes.index', ['buscar' => "' OR '1'='1' --"]))
            ->assertOk()->assertSee('No hay aspirantes con los filtros seleccionados.');
    }

    /** CP-SEG-05 */
    public function test_las_contrasenas_se_almacenan_con_bcrypt(): void
    {
        $hash = $this->aspirante()->contrasena_hash;

        $this->assertMatchesRegularExpression('/^\$2y\$\d{2}\$/', $hash);
        $this->assertSame('bcrypt', password_get_info($hash)['algoName']);
    }

    /** CP-SEG-06 */
    public function test_la_pregunta_del_diagnostico_no_expone_la_respuesta_correcta(): void
    {
        $this->sembrarCatalogo();
        $aspirante = $this->aspirante();
        $this->actingAs($aspirante)->post(route('aspirante.diagnostico.iniciar', 'pretest'));

        $html = $this->get(route('aspirante.diagnostico.mostrar', Diagnostico::firstOrFail()))->assertOk()->getContent();

        $this->assertStringNotContainsString('es_correcta', $html);
        $this->assertStringNotContainsString('data-correcta', $html);
    }

    /** CP-SEG-07 */
    public function test_los_recursos_externos_abren_con_rel_noopener(): void
    {
        $this->sembrarCatalogo();
        $aspirante = $this->aspirante();
        $rutaModulo = $this->completarDiagnostico($aspirante, false)->perfil->ruta->modulos->first();
        $recurso = RecursoAprendizaje::create(['id_modulo' => $rutaModulo->id_modulo, 'titulo' => 'Externo', 'tipo' => 'articulo', 'url' => 'https://recursos.example.org/a', 'estado' => 'activo']);

        $this->actingAs($aspirante)->get(route('aspirante.recurso', [$rutaModulo, $recurso]))->assertSee('rel="noopener noreferrer"', false);
    }
}
