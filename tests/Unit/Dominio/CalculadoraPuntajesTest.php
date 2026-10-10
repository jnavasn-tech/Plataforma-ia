<?php

namespace Tests\Unit\Dominio;

use App\Dominio\Diagnostico\CalculadoraPuntajes;
use App\Dominio\Diagnostico\RespuestaRegistrada;
use PHPUnit\Framework\TestCase;

/**
 * PU-PUN-01 a PU-PUN-05. RF-02, RF-03, CU-02.
 */
class CalculadoraPuntajesTest extends TestCase
{
    private CalculadoraPuntajes $calculadora;

    protected function setUp(): void
    {
        $this->calculadora = new CalculadoraPuntajes();
    }

    /** PU-PUN-01 */
    public function test_todas_correctas_equivale_a_cien(): void
    {
        $respuestas = [
            new RespuestaRegistrada(1, 'OFI', 2, true),
            new RespuestaRegistrada(2, 'OFI', 3, true),
        ];

        $this->assertSame(100.0, $this->calculadora->puntajeArea($respuestas));
    }

    /** PU-PUN-02 */
    public function test_ninguna_correcta_equivale_a_cero(): void
    {
        $respuestas = [
            new RespuestaRegistrada(1, 'OFI', 2, false),
            new RespuestaRegistrada(2, 'OFI', 1, false),
        ];

        $this->assertSame(0.0, $this->calculadora->puntajeArea($respuestas));
    }

    /** PU-PUN-03 */
    public function test_el_puntaje_pondera_por_dificultad(): void
    {
        // (2 + 1) / (2 + 3 + 1) * 100 = 50.00
        $respuestas = [
            new RespuestaRegistrada(1, 'OFI', 2, true),
            new RespuestaRegistrada(2, 'OFI', 3, false),
            new RespuestaRegistrada(3, 'OFI', 1, true),
        ];

        $this->assertSame(50.0, $this->calculadora->puntajeArea($respuestas));
    }

    /** PU-PUN-04 */
    public function test_area_sin_respuestas_vale_cero_y_se_agrupan_por_area(): void
    {
        $respuestas = [
            new RespuestaRegistrada(1, 'OFI', 1, true),
            new RespuestaRegistrada(2, 'RED', 3, false),
        ];

        $this->assertSame(
            ['OFI' => 100.0, 'RED' => 0.0, 'SEG' => 0.0],
            $this->calculadora->puntajesPorArea($respuestas, ['OFI', 'RED', 'SEG'])
        );
    }

    /** PU-PUN-05 */
    public function test_el_puntaje_global_es_el_promedio_de_las_areas(): void
    {
        $this->assertSame(55.5, $this->calculadora->puntajeGlobal(['OFI' => 100.0, 'RED' => 11.0, 'SEG' => 55.5]));
        $this->assertSame(0.0, $this->calculadora->puntajeGlobal([]));
    }
}
