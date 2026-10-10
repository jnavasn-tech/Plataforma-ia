<?php

namespace Tests\Feature\Admin;

use App\Models\Usuario;
use App\Services\Reportes\ServicioReportes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/** CP-REP-01 a CP-REP-05. CU-06 "Generar reporte de competencias" (PDF y Excel). */
class ReportesTest extends TestCase
{
    use CreaEscenarios, RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCatalogo();
        $this->admin = $this->administrador();
    }

    /** CP-REP-01 */
    public function test_sin_datos_muestra_el_mensaje_del_caso_de_uso(): void
    {
        $mensaje = 'No hay datos disponibles para el período seleccionado. Ajuste el rango de fechas o verifique el proceso de selección.';

        $this->actingAs($this->admin)->get(route('admin.reportes.index', ['consultar' => 1]))->assertOk()->assertSee($mensaje);
        $this->get(route('admin.reportes.exportar', ['formato' => 'pdf']))->assertRedirect()->assertSessionHas('aviso', $mensaje);
    }

    /** CP-REP-02 */
    public function test_el_ranking_ordena_por_puntaje_global(): void
    {
        $bajo = $this->aspirante();
        $alto = $this->aspirante();
        $this->completarDiagnostico($bajo, false);
        $this->completarDiagnostico($alto, fn (int $orden, string $area) => in_array($area, ['OFI', 'RED'], true));

        $filas = app(ServicioReportes::class)->comparativo(null, null, null);

        $this->assertSame([$alto->id_usuario, $bajo->id_usuario], $filas->pluck('id_usuario')->all());
        $this->assertSame([1, 2], $filas->pluck('posicion')->all());
        $this->assertSame(40.0, $filas[0]['pretest']);
        $this->actingAs($this->admin)->get(route('admin.reportes.index', ['consultar' => 1]))->assertOk()->assertSee('Exportar PDF');
    }

    /** CP-REP-03 */
    public function test_exporta_el_reporte_en_pdf_y_registra_la_bitacora(): void
    {
        $this->completarDiagnostico($this->aspirante(), false);

        $respuesta = $this->actingAs($this->admin)->get(route('admin.reportes.exportar', ['formato' => 'pdf']));

        $respuesta->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
        $this->assertDatabaseHas('bitacora', ['accion' => 'reporte_generado', 'id_usuario' => $this->admin->id_usuario]);
    }

    /** CP-REP-04 */
    public function test_exporta_el_reporte_en_excel(): void
    {
        $this->completarDiagnostico($this->aspirante(), false);

        $respuesta = $this->actingAs($this->admin)->get(route('admin.reportes.exportar', ['formato' => 'xlsx']));

        $respuesta->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', (string) $respuesta->headers->get('content-disposition'));
        $this->assertStringStartsWith('PK', $respuesta->streamedContent());
    }

    /** CP-REP-05 */
    public function test_reporte_individual_requiere_aspirante(): void
    {
        $this->actingAs($this->admin)->get(route('admin.reportes.index', ['consultar' => 1, 'tipo' => 'individual']))->assertSessionHasErrors('id_usuario');
    }
}
