<?php

namespace App\Services\Reportes;

use App\Dominio\Reportes\GraficoRadarSvg;
use App\Enums\EstadoDiagnostico;
use App\Enums\TipoDiagnostico;
use App\Models\Diagnostico;
use App\Models\Usuario;
use App\Services\Bitacora\RegistradorBitacora;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Caso de uso CU-06 "Generar reporte de competencias": tabla comparativa, ranking por puntaje global
 * y gráfico de radar por aspirante, con exportación a PDF (DomPDF) y Excel (PhpSpreadsheet).
 * El ranking es información de apoyo; no constituye una decisión de contratación.
 */
class ServicioReportes
{
    public function __construct(
        private readonly GraficoRadarSvg $radar,
        private readonly RegistradorBitacora $bitacora,
    ) {
    }

    /**
     * @return Collection<int,array<string,mixed>>
     */
    public function comparativo(?int $idProceso, ?string $desde, ?string $hasta, ?int $idUsuario = null): Collection
    {
        $pretests = Diagnostico::query()
            ->with(['usuario.postulaciones.proceso', 'perfil.ruta.modulos'])
            ->where('tipo', TipoDiagnostico::Pretest->value)
            ->where('estado', EstadoDiagnostico::Completado->value)
            ->when($idProceso, fn ($q) => $q->where('id_proceso', $idProceso))
            ->when($desde, fn ($q) => $q->whereDate('finalizado_en', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('finalizado_en', '<=', $hasta))
            ->when($idUsuario, fn ($q) => $q->where('id_usuario', $idUsuario))
            ->orderByDesc('finalizado_en')
            ->get()
            ->unique('id_usuario');

        $postests = Diagnostico::query()
            ->with('perfil')
            ->where('tipo', TipoDiagnostico::Postest->value)
            ->where('estado', EstadoDiagnostico::Completado->value)
            ->whereIn('id_usuario', $pretests->pluck('id_usuario'))
            ->orderByDesc('finalizado_en')
            ->get()
            ->unique('id_usuario')
            ->keyBy('id_usuario');

        $filas = $pretests->map(function (Diagnostico $pretest) use ($postests) {
            $perfil = $pretest->perfil;
            $postest = $postests->get($pretest->id_usuario)?->perfil;
            $postulacion = $pretest->usuario->postulaciones->firstWhere('id_proceso', $pretest->id_proceso)
                ?? $pretest->usuario->postulaciones->sortByDesc('created_at')->first();

            return [
                'id_usuario' => $pretest->id_usuario,
                'nombre' => $pretest->usuario->nombre,
                'puesto' => $postulacion?->proceso?->puesto ?? 'Sin proceso',
                'estado' => $postulacion?->estado?->etiqueta() ?? 'Sin postulación',
                'fecha_diagnostico' => $pretest->finalizado_en?->format('d/m/Y'),
                'pretest' => $perfil?->puntaje_global,
                'nivel' => $perfil?->nivel_general?->etiqueta(),
                'areas' => $perfil?->puntajesPorNombre() ?? [],
                'postest' => $postest?->puntaje_global,
                'areas_postest' => $postest?->puntajesPorNombre(),
                'mejora' => $postest && $perfil ? round($postest->puntaje_global - $perfil->puntaje_global, 2) : null,
                'avance_ruta' => $perfil?->ruta?->porcentajeAvance() ?? 0.0,
                'puntaje_actual' => $postest?->puntaje_global ?? $perfil?->puntaje_global ?? 0.0,
            ];
        })->filter(fn (array $f) => $f['pretest'] !== null)
            ->sortByDesc('puntaje_actual')
            ->values()
            ->map(fn (array $f, int $i) => ['posicion' => $i + 1] + $f);

        return $filas;
    }

    /** @param Collection<int,array<string,mixed>> $filas */
    public function graficos(Collection $filas, bool $paraPdf = false): array
    {
        $graficos = [];
        foreach ($filas as $fila) {
            if (count($fila['areas']) >= 3) {
                $svg = $this->radar->generar($fila['areas'], 'Perfil de competencias de '.$fila['nombre'], 260, $fila['areas_postest'], ! $paraPdf);
                $graficos[$fila['id_usuario']] = $this->radar->comoDataUri($svg);
            }
        }

        return $graficos;
    }

    /** @param Collection<int,array<string,mixed>> $filas */
    public function exportarPdf(Collection $filas, array $filtros, Usuario $administrador): Response
    {
        $this->bitacora->registrar('reporte_generado', $administrador, null, ['formato' => 'pdf', 'registros' => $filas->count()] + $filtros);

        return Pdf::loadView('admin.reportes.pdf', [
            'filas' => $filas,
            'graficos' => $this->graficos($filas, true),
            'filtros' => $filtros,
            'generado' => now(),
        ])->setPaper('letter', 'landscape')->download('reporte_competencias_'.now()->format('Ymd_His').'.pdf');
    }

    /** @param Collection<int,array<string,mixed>> $filas */
    public function exportarExcel(Collection $filas, array $filtros, Usuario $administrador): StreamedResponse
    {
        $this->bitacora->registrar('reporte_generado', $administrador, null, ['formato' => 'xlsx', 'registros' => $filas->count()] + $filtros);

        $areas = $filas->flatMap(fn ($f) => array_keys($f['areas']))->unique()->values()->all();
        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Comparativo');

        $encabezados = array_merge(['Posición', 'Aspirante', 'Puesto', 'Estado', 'Fecha diagnóstico', 'Puntaje pretest', 'Nivel'], $areas, ['Puntaje postest', 'Mejora (pp)', 'Avance ruta (%)']);
        $hoja->fromArray($encabezados, null, 'A1');
        $hoja->getStyle('1:1')->getFont()->setBold(true);

        $filaExcel = 2;
        foreach ($filas as $fila) {
            $valores = [$fila['posicion'], $fila['nombre'], $fila['puesto'], $fila['estado'], $fila['fecha_diagnostico'], $fila['pretest'], $fila['nivel']];
            foreach ($areas as $area) {
                $valores[] = $fila['areas'][$area] ?? null;
            }
            $valores = array_merge($valores, [$fila['postest'], $fila['mejora'], $fila['avance_ruta']]);
            $hoja->fromArray($valores, null, 'A'.$filaExcel++, true);
        }
        foreach (range(1, count($encabezados)) as $columna) {
            $hoja->getColumnDimensionByColumn($columna)->setAutoSize(true);
        }
        $hoja->setCellValue('A'.($filaExcel + 1), 'Nota: el ranking es información de apoyo; la decisión de contratación corresponde a RRHH.');

        return response()->streamDownload(function () use ($libro) {
            (new Xlsx($libro))->save('php://output');
        }, 'reporte_competencias_'.now()->format('Ymd_His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
