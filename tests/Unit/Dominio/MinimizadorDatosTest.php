<?php

namespace Tests\Unit\Dominio;

use App\Dominio\IA\DatosPersonalesDetectadosException;
use App\Dominio\IA\MinimizadorDatos;
use PHPUnit\Framework\TestCase;

/**
 * PU-PRI-01 a PU-PRI-04. RNF-03, privacidad por diseño en la integración con IA.
 */
class MinimizadorDatosTest extends TestCase
{
    private MinimizadorDatos $minimizador;

    protected function setUp(): void
    {
        $this->minimizador = new MinimizadorDatos();
    }

    private function areas(): array
    {
        return [
            ['codigo' => 'OFI', 'nombre' => 'Ofimática', 'puntaje' => 41.666, 'nivel' => 'basico'],
            ['codigo' => 'SEG', 'nombre' => 'Ciberseguridad', 'puntaje' => 88.0, 'nivel' => 'avanzado'],
        ];
    }

    /** PU-PRI-01 */
    public function test_la_entrada_de_ruta_solo_contiene_datos_pedagogicos(): void
    {
        $entrada = $this->minimizador->entradaRuta(
            $this->areas(), 'intermedio', 64.83,
            [['id_modulo' => 11, 'area' => 'OFI', 'nombre' => 'Procesador de texto', 'nivel' => 'basico']],
            'Auxiliar administrativo', 10, ['OFI']
        );

        $this->assertSame(['perfil', 'puesto_objetivo', 'catalogo_modulos', 'restricciones'], array_keys($entrada));
        $this->assertSame(41.67, $entrada['perfil']['areas'][0]['puntaje']);
        $json = json_encode($entrada);
        $this->assertStringNotContainsString('correo', $json);
        $this->assertStringNotContainsString('id_usuario', $json);
    }

    /** PU-PRI-02 */
    public function test_bloquea_claves_personales(): void
    {
        $this->expectException(DatosPersonalesDetectadosException::class);
        $this->minimizador->asegurarSinDatosPersonales(['perfil' => ['correo' => 'x']]);
    }

    /** PU-PRI-03 */
    public function test_bloquea_correos_y_telefonos_en_valores(): void
    {
        foreach (['aspirante@example.com', 'Llamar al 5555-1234', '+502 2222 3333'] as $valor) {
            try {
                $this->minimizador->asegurarSinDatosPersonales(['puesto_objetivo' => $valor]);
                $this->fail("No se bloqueó: {$valor}");
            } catch (DatosPersonalesDetectadosException) {
                $this->assertTrue(true);
            }
        }
    }

    /** PU-PRI-04 */
    public function test_la_entrada_de_retroalimentacion_limita_temas_y_longitud(): void
    {
        $temas = array_fill(0, 8, str_repeat('t', 300));

        $entrada = $this->minimizador->entradaRetroalimentacion('Hojas de cálculo', 'Ofimática', 'basico', 50.0, false, 1, 3, $temas);

        $this->assertCount(5, $entrada['evaluacion']['temas_a_reforzar']);
        $this->assertSame(150, mb_strlen($entrada['evaluacion']['temas_a_reforzar'][0]));
    }
}
