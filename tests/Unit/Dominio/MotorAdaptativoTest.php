<?php

namespace Tests\Unit\Dominio;

use App\Dominio\Diagnostico\BancoInsuficienteException;
use App\Dominio\Diagnostico\ItemBanco;
use App\Dominio\Diagnostico\MotorAdaptativo;
use App\Dominio\Diagnostico\RespuestaRegistrada;
use PHPUnit\Framework\TestCase;

/**
 * PU-DIA-01 a PU-DIA-08. RF-02, CU-02.
 */
class MotorAdaptativoTest extends TestCase
{
    private const AREAS = ['OFI', 'RED', 'SEG', 'COL', 'PRO'];

    /** Banco de 12 ítems por área (4 por nivel de dificultad). */
    private function banco(int $porNivel = 4): array
    {
        $banco = [];
        $id = 1;
        foreach (self::AREAS as $area) {
            foreach ([1, 2, 3] as $dificultad) {
                for ($i = 0; $i < $porNivel; $i++) {
                    $banco[] = new ItemBanco($id++, $area, $dificultad);
                }
            }
        }

        return $banco;
    }

    /** Selección determinística: siempre el primer candidato. */
    private function motor(int $itemsPorArea = 6): MotorAdaptativo
    {
        return new MotorAdaptativo($itemsPorArea, fn (int $min, int $max) => $min);
    }

    /** PU-DIA-01 */
    public function test_el_primer_item_tiene_dificultad_media_y_pertenece_a_la_primera_area(): void
    {
        $item = $this->motor()->siguienteItem(self::AREAS, $this->banco(), []);

        $this->assertSame('OFI', $item->codigoArea);
        $this->assertSame(MotorAdaptativo::DIFICULTAD_INICIAL, $item->dificultad);
    }

    /** PU-DIA-02 */
    public function test_la_dificultad_sube_tras_acierto_y_baja_tras_error(): void
    {
        $motor = $this->motor();

        $this->assertSame(3, $motor->dificultadObjetivo([new RespuestaRegistrada(1, 'OFI', 2, true)]));
        $this->assertSame(1, $motor->dificultadObjetivo([new RespuestaRegistrada(1, 'OFI', 2, false)]));
    }

    /** PU-DIA-03 */
    public function test_la_dificultad_se_mantiene_dentro_de_los_limites(): void
    {
        $motor = $this->motor();

        $this->assertSame(3, $motor->dificultadObjetivo([new RespuestaRegistrada(1, 'OFI', 3, true)]));
        $this->assertSame(1, $motor->dificultadObjetivo([new RespuestaRegistrada(1, 'OFI', 1, false)]));
    }

    /** PU-DIA-04 */
    public function test_aplica_treinta_items_sin_repetir_y_finaliza_con_null(): void
    {
        $motor = $this->motor();
        $banco = $this->banco();
        $historial = [];
        $aciertoAlterno = true;

        while (($item = $motor->siguienteItem(self::AREAS, $banco, $historial)) !== null) {
            $historial[] = new RespuestaRegistrada($item->idPregunta, $item->codigoArea, $item->dificultad, $aciertoAlterno);
            $aciertoAlterno = ! $aciertoAlterno;
            $this->assertLessThanOrEqual(30, count($historial));
        }

        $ids = array_map(fn ($r) => $r->idPregunta, $historial);
        $this->assertCount(30, $historial);
        $this->assertSame($ids, array_values(array_unique($ids)));
        foreach (self::AREAS as $area) {
            $this->assertCount(6, array_filter($historial, fn ($r) => $r->codigoArea === $area));
        }
    }

    /** PU-DIA-05 */
    public function test_elige_la_dificultad_mas_cercana_cuando_no_hay_items_del_nivel_objetivo(): void
    {
        $banco = [new ItemBanco(1, 'OFI', 1), new ItemBanco(2, 'OFI', 1), new ItemBanco(3, 'OFI', 2)];
        $historial = [new RespuestaRegistrada(3, 'OFI', 2, true)]; // objetivo 3, no existe

        $item = $this->motor(3)->siguienteItem(['OFI'], $banco, $historial);

        $this->assertSame(1, $item->dificultad);
    }

    /** PU-DIA-06 */
    public function test_excluye_el_item_pendiente_de_respuesta(): void
    {
        $banco = [new ItemBanco(1, 'OFI', 2), new ItemBanco(2, 'OFI', 2)];

        $item = $this->motor(2)->siguienteItem(['OFI'], $banco, [], [1]);

        $this->assertSame(2, $item->idPregunta);
    }

    /** PU-DIA-07 */
    public function test_lanza_excepcion_si_el_area_no_tiene_items_disponibles(): void
    {
        $this->expectException(BancoInsuficienteException::class);

        $banco = [new ItemBanco(1, 'OFI', 2)];
        $historial = [new RespuestaRegistrada(1, 'OFI', 2, true)];
        $this->motor(2)->siguienteItem(['OFI'], $banco, $historial);
    }

    /** PU-DIA-08 */
    public function test_detecta_areas_con_banco_insuficiente(): void
    {
        $banco = array_filter($this->banco(1), fn (ItemBanco $i) => $i->codigoArea !== 'SEG');

        $this->assertSame(['OFI', 'RED', 'SEG', 'COL', 'PRO'], $this->motor(4)->areasInsuficientes(self::AREAS, $banco));
        $this->assertSame(['SEG'], $this->motor(3)->areasInsuficientes(self::AREAS, $banco));
        $this->assertSame(30, $this->motor()->totalItems(self::AREAS));
    }
}
