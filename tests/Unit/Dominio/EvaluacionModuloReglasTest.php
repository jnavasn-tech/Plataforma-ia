<?php

namespace Tests\Unit\Dominio;

use App\Dominio\Evaluacion\CalificadorEvaluacion;
use App\Dominio\Evaluacion\RetroalimentacionReglas;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * PU-EVA-01 a PU-EVA-04. RF-06, RF-07, CU-04 (actividad de verificación).
 */
class EvaluacionModuloReglasTest extends TestCase
{
    /** PU-EVA-01 */
    public function test_calcula_calificacion_y_aprobacion_en_el_limite(): void
    {
        $calificador = new CalificadorEvaluacion();

        $this->assertSame(75.0, $calificador->calificar(3, 4));
        $this->assertSame(66.67, $calificador->calificar(2, 3));
        $this->assertTrue($calificador->aprobado(70.0));
        $this->assertFalse($calificador->aprobado(69.99));
    }

    /** PU-EVA-02 */
    public function test_controla_intentos_y_desbloqueo(): void
    {
        $calificador = new CalificadorEvaluacion();

        $this->assertTrue($calificador->puedeIntentar(2, false));
        $this->assertFalse($calificador->puedeIntentar(3, false));
        $this->assertFalse($calificador->puedeIntentar(1, true));
        $this->assertTrue($calificador->moduloConcluido(3, false));
        $this->assertTrue($calificador->moduloConcluido(1, true));
        $this->assertFalse($calificador->moduloConcluido(2, false));
    }

    /** PU-EVA-03 */
    public function test_rechaza_entradas_invalidas(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CalificadorEvaluacion())->calificar(5, 4);
    }

    /** PU-EVA-04 */
    public function test_retroalimentacion_por_reglas_segun_resultado(): void
    {
        $reglas = new RetroalimentacionReglas();

        $excelente = $reglas->generar('Hojas de cálculo', 100.0, true, 1, 3, []);
        $reintento = $reglas->generar('Hojas de cálculo', 50.0, false, 1, 3, ['Uso de la función SUMA']);
        $agotado = $reglas->generar('Hojas de cálculo', 25.0, false, 3, 3, []);

        $this->assertStringContainsString('Excelente', $excelente['mensaje']);
        $this->assertStringContainsString('Le recomendamos revisar el recurso', $reintento['mensaje']);
        $this->assertSame(['Repase el tema: Uso de la función SUMA'], $reintento['sugerencias']);
        $this->assertStringContainsString('2 de 3', $reintento['siguiente_paso']);
        $this->assertStringContainsString('desbloqueado', $agotado['siguiente_paso']);
    }
}
