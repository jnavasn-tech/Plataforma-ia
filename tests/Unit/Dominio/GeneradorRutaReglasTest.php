<?php

namespace Tests\Unit\Dominio;

use App\Dominio\Perfil\ClasificadorNivel;
use App\Dominio\Rutas\CatalogoInsuficienteException;
use App\Dominio\Rutas\GeneradorRutaReglas;
use App\Dominio\Rutas\ModuloCatalogo;
use App\Enums\NivelCompetencia;
use App\Enums\Prioridad;
use PHPUnit\Framework\TestCase;

/**
 * PU-RUT-01 a PU-RUT-06. RF-04, CU-03 (flujo principal y fallback).
 */
class GeneradorRutaReglasTest extends TestCase
{
    private const NOMBRES = ['OFI' => 'Ofimática', 'RED' => 'Redes e Internet', 'SEG' => 'Ciberseguridad', 'COL' => 'Colaboración digital', 'PRO' => 'Productividad digital'];

    /** Catálogo: id = posición del área * 10 + nivel (1 básico, 2 intermedio, 3 avanzado). */
    private function catalogo(): array
    {
        $catalogo = [];
        foreach (array_keys(self::NOMBRES) as $i => $area) {
            foreach ([NivelCompetencia::Basico, NivelCompetencia::Intermedio, NivelCompetencia::Avanzado] as $nivel) {
                $catalogo[] = new ModuloCatalogo(($i + 1) * 10 + $nivel->orden(), $area, "{$area} {$nivel->value}", $nivel);
            }
        }

        return $catalogo;
    }

    private function generador(int $max = 10): GeneradorRutaReglas
    {
        return new GeneradorRutaReglas(new ClasificadorNivel(), $max);
    }

    /** PU-RUT-01 */
    public function test_prioriza_areas_basicas_por_mayor_brecha_y_excluye_avanzadas(): void
    {
        $puntajes = ['OFI' => 30.0, 'RED' => 60.0, 'SEG' => 90.0, 'COL' => 45.0, 'PRO' => 85.0];

        $plan = $this->generador()->generar($puntajes, self::NOMBRES, $this->catalogo());

        $this->assertSame([11, 12, 41, 42, 22, 23], $plan->idsModulos());
        $this->assertSame(Prioridad::Alta, $plan->modulos[0]->prioridad);
        $this->assertSame(Prioridad::Media, $plan->modulos[4]->prioridad);
        $this->assertSame([1, 2, 3, 4, 5, 6], array_map(fn ($m) => $m->orden, $plan->modulos));
        $this->assertFalse($plan->esRutaAvanzada);
        $this->assertStringContainsString('Ofimática', $plan->narrativa);
    }

    /** PU-RUT-02 */
    public function test_perfil_maximo_genera_ruta_avanzada_opcional(): void
    {
        $puntajes = ['OFI' => 80.0, 'RED' => 95.0, 'SEG' => 90.0, 'COL' => 100.0, 'PRO' => 85.0];

        $plan = $this->generador()->generar($puntajes, self::NOMBRES, $this->catalogo());

        $this->assertTrue($plan->esRutaAvanzada);
        $this->assertCount(5, $plan->modulos);
        foreach ($plan->modulos as $modulo) {
            $this->assertSame(Prioridad::Baja, $modulo->prioridad);
            $this->assertSame(3, $modulo->idModulo % 10);
        }
    }

    /** PU-RUT-03 */
    public function test_omite_modulos_sin_recursos(): void
    {
        $catalogo = array_map(
            fn (ModuloCatalogo $m) => $m->idModulo === 11 ? new ModuloCatalogo(11, 'OFI', 'sin recursos', NivelCompetencia::Basico, false) : $m,
            $this->catalogo()
        );

        $plan = $this->generador()->generar(['OFI' => 10.0, 'RED' => 85.0, 'SEG' => 85.0, 'COL' => 85.0, 'PRO' => 85.0], self::NOMBRES, $catalogo);

        $this->assertSame([12], $plan->idsModulos());
    }

    /** PU-RUT-04 */
    public function test_lanza_excepcion_si_no_hay_modulos_utilizables(): void
    {
        $this->expectException(CatalogoInsuficienteException::class);

        $this->generador()->generar(['OFI' => 10.0, 'RED' => 85.0], self::NOMBRES, []);
    }

    /** PU-RUT-05 */
    public function test_respeta_el_maximo_de_modulos(): void
    {
        $puntajes = ['OFI' => 10.0, 'RED' => 20.0, 'SEG' => 30.0, 'COL' => 40.0, 'PRO' => 45.0];

        $plan = $this->generador(4)->generar($puntajes, self::NOMBRES, $this->catalogo());

        $this->assertSame([11, 12, 21, 22], $plan->idsModulos());
    }

    /** PU-RUT-06 */
    public function test_identifica_areas_obligatorias(): void
    {
        $this->assertSame(['OFI', 'COL'], $this->generador()->areasObligatorias(['OFI' => 30.0, 'RED' => 60.0, 'COL' => 49.99]));
    }
}
