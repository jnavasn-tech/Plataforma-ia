<?php

namespace Tests\Feature\IA;

use App\Enums\OrigenGeneracion;
use App\Models\BitacoraIA;
use App\Models\ModuloContenido;
use App\Models\PerfilCompetencia;
use App\Models\RutaAprendizaje;
use App\Models\Usuario;
use App\Services\Rutas\ServicioRutas;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/**
 * CP-IA-01 a CP-IA-13. RF-04, RF-07, CU-03 (flujo principal y alterno de API no disponible).
 * El proveedor se simula con Http::fake(): no se consume la API real ni se envían datos personales.
 */
class IntegracionIATest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    private Usuario $aspirante;

    private PerfilCompetencia $perfil;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCatalogo();
        // Perfil básico en las cinco áreas generado sin IA; luego se elimina la ruta para regenerarla en cada prueba.
        $this->aspirante = $this->aspirante();
        $this->perfil = $this->completarDiagnostico($this->aspirante, false)->perfil;
        $this->perfil->ruta()->delete();
        BitacoraIA::query()->delete();
        $this->habilitarIA();
    }

    /** Ruta válida: un módulo básico por cada área (todas las áreas son obligatorias en este perfil). */
    private function rutaValida(): array
    {
        return [
            'modulos' => ModuloContenido::where('nivel', 'basico')->orderBy('id_modulo')->get()->map(fn ($m) => [
                'id_modulo' => $m->id_modulo, 'prioridad' => 'alta', 'justificacion' => 'Atiende la brecha del área con puntaje 0/100.',
            ])->all(),
            'narrativa' => 'Inicie por los fundamentos de cada área y avance de forma constante.',
            'fortalezas' => [],
            'areas_mejora' => ['Ofimática', 'Redes e Internet', 'Ciberseguridad', 'Colaboración digital', 'Productividad digital'],
        ];
    }

    private function generar(): RutaAprendizaje
    {
        return app(ServicioRutas::class)->generarParaPerfil($this->perfil->fresh());
    }

    /** CP-IA-01 */
    public function test_ruta_valida_de_la_ia_se_persiste_con_modelo_y_version_de_prompt(): void
    {
        Http::fake(['api.openai.com/*' => Http::response($this->respuestaOpenAI($this->rutaValida()))]);

        $ruta = $this->generar();

        $this->assertSame(OrigenGeneracion::IA, $ruta->origen);
        $this->assertSame('ruta-v1', $ruta->version_prompt);
        $this->assertSame('gpt-4o-mini', $ruta->modelo_ia);
        $this->assertCount(5, $ruta->modulos);
        $this->assertDatabaseHas('bitacora_ia', ['tipo' => 'ruta', 'estado' => 'exito', 'version_prompt' => 'ruta-v1', 'tokens_entrada' => 420]);
    }

    /** CP-IA-02 */
    public function test_json_invalido_activa_el_fallback(): void
    {
        Http::fake(['api.openai.com/*' => Http::response($this->respuestaOpenAI('esto no es JSON'))]);

        $this->assertSame(OrigenGeneracion::Reglas, $this->generar()->origen);
        $this->assertDatabaseHas('bitacora_ia', ['tipo' => 'ruta', 'estado' => 'fallback', 'codigo_error' => 'json_invalido']);
    }

    /** CP-IA-03 */
    public function test_tiempo_de_espera_agotado_activa_el_fallback(): void
    {
        Http::fake(fn () => throw new ConnectException('cURL error 28: Operation timed out', new Psr7Request('POST', 'https://api.openai.com/v1/chat/completions')));

        $ruta = $this->generar();

        $this->assertSame(OrigenGeneracion::Reglas, $ruta->origen);
        $this->assertNotEmpty($ruta->modulos);
        $this->assertDatabaseHas('bitacora_ia', ['estado' => 'fallback', 'codigo_error' => 'timeout', 'intentos' => 2]);
    }

    /** CP-IA-04 */
    public function test_error_500_reintenta_y_luego_aplica_el_fallback(): void
    {
        Http::fake(['api.openai.com/*' => Http::sequence()->push(['error' => 'x'], 500)->push(['error' => 'x'], 503)]);

        $this->assertSame(OrigenGeneracion::Reglas, $this->generar()->origen);
        Http::assertSentCount(2);
        $this->assertDatabaseHas('bitacora_ia', ['codigo_error' => 'http_503', 'intentos' => 2]);
    }

    /** CP-IA-05 */
    public function test_error_de_autorizacion_no_se_reintenta(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'invalid key']], 401)]);

        $this->assertSame(OrigenGeneracion::Reglas, $this->generar()->origen);
        Http::assertSentCount(1);
        $this->assertDatabaseHas('bitacora_ia', ['codigo_error' => 'http_401']);
    }

    /** CP-IA-06 */
    public function test_un_reintento_exitoso_conserva_la_ruta_de_la_ia(): void
    {
        Http::fake(['api.openai.com/*' => Http::sequence()->push(['error' => 'x'], 429)->push($this->respuestaOpenAI($this->rutaValida()))]);

        $this->assertSame(OrigenGeneracion::IA, $this->generar()->origen);
        $this->assertDatabaseHas('bitacora_ia', ['estado' => 'exito', 'intentos' => 2]);
    }

    /** CP-IA-07 */
    public function test_modulo_fuera_del_catalogo_activa_el_fallback(): void
    {
        $datos = $this->rutaValida();
        $datos['modulos'][0]['id_modulo'] = 99999;
        Http::fake(['api.openai.com/*' => Http::response($this->respuestaOpenAI($datos))]);

        $this->assertSame(OrigenGeneracion::Reglas, $this->generar()->origen);
        $this->assertDatabaseHas('bitacora_ia', ['codigo_error' => 'modulo_desconocido']);
    }

    /** CP-IA-08 */
    public function test_ruta_que_omite_un_area_basica_activa_el_fallback(): void
    {
        $datos = $this->rutaValida();
        array_pop($datos['modulos']);
        Http::fake(['api.openai.com/*' => Http::response($this->respuestaOpenAI($datos))]);

        $this->assertSame(OrigenGeneracion::Reglas, $this->generar()->origen);
        $this->assertDatabaseHas('bitacora_ia', ['codigo_error' => 'cobertura_insuficiente']);
    }

    /** CP-IA-09 */
    public function test_contenido_sobre_contratacion_activa_el_fallback(): void
    {
        $datos = $this->rutaValida();
        $datos['narrativa'] = 'Con esta ruta se recomienda su contratación inmediata.';
        Http::fake(['api.openai.com/*' => Http::response($this->respuestaOpenAI($datos))]);

        $this->assertSame(OrigenGeneracion::Reglas, $this->generar()->origen);
        $this->assertDatabaseHas('bitacora_ia', ['codigo_error' => 'contenido_no_permitido']);
    }

    /** CP-IA-10 */
    public function test_rechazo_del_modelo_activa_el_fallback(): void
    {
        Http::fake(['api.openai.com/*' => Http::response($this->respuestaOpenAI('', ['choices' => [['message' => ['content' => null, 'refusal' => 'No puedo ayudar con eso.']]]]))]);

        $this->assertSame(OrigenGeneracion::Reglas, $this->generar()->origen);
        $this->assertDatabaseHas('bitacora_ia', ['codigo_error' => 'rechazo_modelo']);
    }

    /** CP-IA-11 */
    public function test_la_solicitud_usa_la_configuracion_y_no_contiene_datos_personales(): void
    {
        config(['ia.modelo' => 'gpt-4o-mini-2024-07-18', 'ia.temperatura' => 0.2]);
        Http::fake(['api.openai.com/*' => Http::response($this->respuestaOpenAI($this->rutaValida()))]);

        $this->generar();

        Http::assertSent(function (Request $solicitud) {
            $cuerpo = $solicitud->body();

            return $solicitud->url() === 'https://api.openai.com/v1/chat/completions'
                && $solicitud->hasHeader('Authorization', 'Bearer clave-de-prueba-no-real')
                && $solicitud['model'] === 'gpt-4o-mini-2024-07-18'
                && $solicitud['temperature'] === 0.2
                && $solicitud['response_format']['type'] === 'json_schema'
                && $solicitud['response_format']['json_schema']['strict'] === true
                && ! str_contains($cuerpo, $this->aspirante->correo)
                && ! str_contains($cuerpo, $this->aspirante->nombre)
                && ! str_contains($cuerpo, 'id_usuario')
                && ! str_contains($cuerpo, 'contrasena');
        });
    }

    /** CP-IA-12 */
    public function test_ia_deshabilitada_no_realiza_solicitudes(): void
    {
        config(['ia.habilitada' => false]);
        Http::fake();

        $this->assertSame(OrigenGeneracion::Reglas, $this->generar()->origen);
        Http::assertNothingSent();
        $this->assertDatabaseHas('bitacora_ia', ['estado' => 'deshabilitada']);
    }

    /** CP-IA-13 */
    public function test_si_la_cola_no_genera_la_ruta_se_aplica_reglas_tras_la_espera_maxima(): void
    {
        Queue::fake();
        config(['ia.habilitada' => false]);
        $otro = $this->aspirante();
        $perfil = $this->completarDiagnostico($otro, false)->perfil;
        $this->assertNull($perfil->ruta);

        $this->travel(config('plataforma.rutas.espera_maxima_segundos') + 5)->seconds();
        $this->actingAs($otro)->get(route('aspirante.ruta'))->assertOk();

        $this->assertSame(OrigenGeneracion::Reglas, $perfil->fresh()->ruta->origen);
        $this->assertDatabaseHas('bitacora_ia', ['codigo_error' => 'espera_excedida']);
    }
}
