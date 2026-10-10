<?php

namespace Tests\Unit\Dominio;

use App\Dominio\IA\RespuestaIAInvalidaException;
use App\Dominio\IA\ValidadorRespuestaIA;
use PHPUnit\Framework\TestCase;

/**
 * PU-IA-01 a PU-IA-11. RF-04, RF-07, CU-03. Validación de JSON, esquema, reglas y contenido.
 */
class ValidadorRespuestaIATest extends TestCase
{
    private ValidadorRespuestaIA $validador;

    private const CATALOGO = [11, 12, 21, 22];

    private const AREA_POR_MODULO = [11 => 'OFI', 12 => 'OFI', 21 => 'RED', 22 => 'RED'];

    protected function setUp(): void
    {
        $this->validador = new ValidadorRespuestaIA();
    }

    private function rutaValida(): array
    {
        return [
            'modulos' => [
                ['id_modulo' => 11, 'prioridad' => 'alta', 'justificacion' => 'Refuerza fundamentos de procesador de texto.'],
                ['id_modulo' => 21, 'prioridad' => 'media', 'justificacion' => 'Consolida conceptos de conectividad.'],
            ],
            'narrativa' => 'Su ruta inicia por ofimática y continúa con redes.',
            'fortalezas' => ['Ciberseguridad básica'],
            'areas_mejora' => ['Ofimática'],
        ];
    }

    private function assertCodigo(string $codigo, callable $accion): void
    {
        try {
            $accion();
            $this->fail("Se esperaba RespuestaIAInvalidaException con código {$codigo}");
        } catch (RespuestaIAInvalidaException $e) {
            $this->assertSame($codigo, $e->codigo);
        }
    }

    /** PU-IA-01 */
    public function test_acepta_una_ruta_valida(): void
    {
        $datos = $this->validador->validarRuta($this->rutaValida(), self::CATALOGO, self::AREA_POR_MODULO, ['OFI'], 10);

        $this->assertCount(2, $datos['modulos']);
    }

    /** PU-IA-02 */
    public function test_rechaza_json_invalido_o_que_no_es_objeto(): void
    {
        $this->assertCodigo('json_invalido', fn () => $this->validador->decodificar('{"modulos": ['));
        $this->assertCodigo('json_invalido', fn () => $this->validador->decodificar('[1, 2, 3]'));
        $this->assertSame(['a' => 1], $this->validador->decodificar(' {"a": 1} '));
    }

    /** PU-IA-03 */
    public function test_rechaza_propiedades_faltantes_o_adicionales(): void
    {
        $sinNarrativa = $this->rutaValida();
        unset($sinNarrativa['narrativa']);
        $conExtra = $this->rutaValida() + ['decision' => 'x'];

        $this->assertCodigo('esquema_invalido', fn () => $this->validador->validarRuta($sinNarrativa, self::CATALOGO, self::AREA_POR_MODULO, [], 10));
        $this->assertCodigo('esquema_invalido', fn () => $this->validador->validarRuta($conExtra, self::CATALOGO, self::AREA_POR_MODULO, [], 10));
    }

    /** PU-IA-04 */
    public function test_rechaza_tipos_incorrectos_y_prioridad_fuera_de_dominio(): void
    {
        $tipo = $this->rutaValida();
        $tipo['modulos'][0]['id_modulo'] = '11';
        $prioridad = $this->rutaValida();
        $prioridad['modulos'][0]['prioridad'] = 'urgente';

        $this->assertCodigo('esquema_invalido', fn () => $this->validador->validarRuta($tipo, self::CATALOGO, self::AREA_POR_MODULO, [], 10));
        $this->assertCodigo('esquema_invalido', fn () => $this->validador->validarRuta($prioridad, self::CATALOGO, self::AREA_POR_MODULO, [], 10));
    }

    /** PU-IA-05 */
    public function test_rechaza_modulos_desconocidos_o_duplicados(): void
    {
        $desconocido = $this->rutaValida();
        $desconocido['modulos'][1]['id_modulo'] = 99;
        $duplicado = $this->rutaValida();
        $duplicado['modulos'][1]['id_modulo'] = 11;

        $this->assertCodigo('modulo_desconocido', fn () => $this->validador->validarRuta($desconocido, self::CATALOGO, self::AREA_POR_MODULO, [], 10));
        $this->assertCodigo('modulo_duplicado', fn () => $this->validador->validarRuta($duplicado, self::CATALOGO, self::AREA_POR_MODULO, [], 10));
    }

    /** PU-IA-06 */
    public function test_rechaza_rutas_que_no_cubren_areas_basicas(): void
    {
        $this->assertCodigo('cobertura_insuficiente', fn () => $this->validador->validarRuta($this->rutaValida(), self::CATALOGO, self::AREA_POR_MODULO, ['OFI', 'SEG'], 10));
    }

    /** PU-IA-07 */
    public function test_rechaza_rutas_que_exceden_el_maximo(): void
    {
        $this->assertCodigo('esquema_invalido', fn () => $this->validador->validarRuta($this->rutaValida(), self::CATALOGO, self::AREA_POR_MODULO, [], 1));
    }

    /** PU-IA-08 */
    public function test_rechaza_decisiones_de_contratacion_y_atributos_protegidos(): void
    {
        $contratacion = $this->rutaValida();
        $contratacion['narrativa'] = 'Con este perfil se recomienda su contratación inmediata.';
        $protegido = $this->rutaValida();
        $protegido['fortalezas'] = ['Por su género tiene ventaja.'];

        $this->assertCodigo('contenido_no_permitido', fn () => $this->validador->validarRuta($contratacion, self::CATALOGO, self::AREA_POR_MODULO, [], 10));
        $this->assertCodigo('contenido_no_permitido', fn () => $this->validador->validarRuta($protegido, self::CATALOGO, self::AREA_POR_MODULO, [], 10));
    }

    /** PU-IA-09 */
    public function test_rechaza_textos_que_exceden_la_longitud_permitida(): void
    {
        $largo = $this->rutaValida();
        $largo['narrativa'] = str_repeat('a', 1501);

        $this->assertCodigo('esquema_invalido', fn () => $this->validador->validarRuta($largo, self::CATALOGO, self::AREA_POR_MODULO, [], 10));
    }

    /** PU-IA-10 */
    public function test_valida_perfil_y_retroalimentacion(): void
    {
        $perfil = ['resumen' => 'Perfil intermedio.', 'fortalezas' => ['Ciberseguridad'], 'areas_mejora' => ['Ofimática'], 'recomendacion_general' => 'Inicie por ofimática.'];
        $retro = ['mensaje' => 'Buen avance.', 'sugerencias' => ['Practique fórmulas.'], 'siguiente_paso' => 'Continúe.'];

        $this->assertSame($perfil, $this->validador->validarPerfil($perfil));
        $this->assertSame($retro, $this->validador->validarRetroalimentacion($retro));
        $this->assertCodigo('esquema_invalido', fn () => $this->validador->validarRetroalimentacion(['mensaje' => '', 'sugerencias' => [], 'siguiente_paso' => 'x']));
    }

    /** PU-IA-11 */
    public function test_permite_vocabulario_tecnico_legitimo(): void
    {
        $datos = ['mensaje' => 'Aprenda a rechazar correos de phishing y a redactar la despedida de un correo formal.', 'sugerencias' => ['Use contraseñas robustas.'], 'siguiente_paso' => 'Continúe.'];

        $this->assertSame($datos, $this->validador->validarRetroalimentacion($datos));
    }
}
