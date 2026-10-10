<?php

namespace Tests\Unit\Dominio;

use App\Dominio\Perfil\ClasificadorNivel;
use App\Enums\NivelCompetencia;
use App\Enums\Prioridad;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * PU-NIV-01 a PU-NIV-04. RF-03, CU-02. Pruebas de valores límite.
 */
class ClasificadorNivelTest extends TestCase
{
    public static function limites(): array
    {
        return [
            'mínimo' => [0.0, NivelCompetencia::Basico],
            'bajo el umbral intermedio' => [49.99, NivelCompetencia::Basico],
            'umbral intermedio' => [50.0, NivelCompetencia::Intermedio],
            'bajo el umbral avanzado' => [79.99, NivelCompetencia::Intermedio],
            'umbral avanzado' => [80.0, NivelCompetencia::Avanzado],
            'máximo' => [100.0, NivelCompetencia::Avanzado],
        ];
    }

    /**
     * PU-NIV-01
     *
     * @dataProvider limites
     */
    #[DataProvider('limites')]
    public function test_clasifica_segun_los_umbrales(float $puntaje, NivelCompetencia $esperado): void
    {
        $this->assertSame($esperado, (new ClasificadorNivel())->clasificar($puntaje));
    }

    /** PU-NIV-02 */
    public function test_rechaza_puntajes_fuera_de_rango(): void
    {
        $clasificador = new ClasificadorNivel();
        foreach ([-0.01, 100.01] as $invalido) {
            try {
                $clasificador->clasificar($invalido);
                $this->fail("Se esperaba excepción para {$invalido}");
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    /** PU-NIV-03 */
    public function test_calcula_prioridad_y_brecha(): void
    {
        $clasificador = new ClasificadorNivel();

        $this->assertSame(Prioridad::Alta, $clasificador->prioridad(42.5));
        $this->assertSame(Prioridad::Media, $clasificador->prioridad(65.0));
        $this->assertSame(Prioridad::Baja, $clasificador->prioridad(95.0));
        $this->assertSame(37.5, $clasificador->brecha(42.5));
        $this->assertSame(0.0, $clasificador->brecha(92.0));
    }

    /** PU-NIV-04 */
    public function test_rechaza_umbrales_incoherentes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ClasificadorNivel(80, 50);
    }
}
