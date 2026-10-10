<?php

namespace Tests\Unit\Dominio;

use App\Dominio\Reportes\GraficoRadarSvg;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * PU-GRA-01 a PU-GRA-03. CU-02 (gráfico de radar), CU-06 (reporte), RNF-04 (accesibilidad).
 */
class GraficoRadarSvgTest extends TestCase
{
    private const SERIE = ['Ofimática' => 40.0, 'Redes' => 65.5, 'Ciberseguridad' => 90.0, 'Colaboración' => 150.0, 'Productividad <x>' => 20.0];

    /** PU-GRA-01 */
    public function test_genera_svg_valido_y_accesible(): void
    {
        $svg = (new GraficoRadarSvg())->generar(self::SERIE, 'Perfil de competencias');

        $xml = simplexml_load_string($svg);
        $this->assertNotFalse($xml);
        $this->assertStringContainsString('<title id="radar-titulo">Perfil de competencias</title>', $svg);
        $this->assertStringContainsString('Productividad &lt;x&gt;', $svg);
        $this->assertSame(5, substr_count($svg, '<text '));
        $this->assertSame(5, substr_count($svg, '<polygon '));
        $this->assertStringContainsString('Colaboración: 100.00', $svg);
    }

    /** PU-GRA-02 */
    public function test_incluye_serie_de_comparacion(): void
    {
        $svg = (new GraficoRadarSvg())->generar(self::SERIE, 'Pretest y postest', 300, ['Ofimática' => 70.0]);

        $this->assertSame(6, substr_count($svg, '<polygon '));
        $this->assertStringStartsWith('data:image/svg+xml;base64,', (new GraficoRadarSvg())->comoDataUri($svg));
    }

    /** PU-GRA-03 */
    public function test_requiere_al_menos_tres_ejes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new GraficoRadarSvg())->generar(['A' => 1.0, 'B' => 2.0], 'x');
    }
}
