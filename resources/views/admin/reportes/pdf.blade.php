<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte de competencias</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
    h1 { font-size: 16px; margin: 0 0 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #999; padding: 3px 4px; }
    th { background: #e5e7eb; }
    .der { text-align: right; }
    .nota { font-size: 8px; color: #444; margin-top: 6px; }
    .grafico { display: inline-block; width: 32%; text-align: center; vertical-align: top; margin-bottom: 6px; }
    .salto { page-break-before: always; }
</style>
</head>
<body>
<h1>Reporte de competencias informáticas</h1>
<div>Generado: {{ $generado->format('d/m/Y H:i') }} · Filtros: {{ collect($filtros)->map(fn ($v, $k) => "$k: $v")->implode(', ') ?: 'sin filtros' }}</div>
<table>
    <thead><tr><th>#</th><th>Aspirante</th><th>Puesto</th><th>Estado</th><th>Fecha</th><th>Pretest</th><th>Nivel</th>
        @foreach(array_keys($filas->first()['areas']) as $area)<th>{{ $area }}</th>@endforeach
        <th>Postest</th><th>Mejora (pp)</th><th>Avance ruta</th></tr></thead>
    <tbody>
    @foreach($filas as $f)
        <tr><td>{{ $f['posicion'] }}</td><td>{{ $f['nombre'] }}</td><td>{{ $f['puesto'] }}</td><td>{{ $f['estado'] }}</td><td>{{ $f['fecha_diagnostico'] }}</td>
            <td class="der">{{ number_format($f['pretest'], 2) }}</td><td>{{ $f['nivel'] }}</td>
            @foreach($f['areas'] as $valor)<td class="der">{{ number_format($valor, 2) }}</td>@endforeach
            <td class="der">{{ $f['postest'] !== null ? number_format($f['postest'], 2) : '—' }}</td><td class="der">{{ $f['mejora'] !== null ? number_format($f['mejora'], 2) : '—' }}</td><td class="der">{{ number_format($f['avance_ruta'], 0) }} %</td></tr>
    @endforeach
    </tbody>
</table>
<p class="nota">Puntajes de 0 a 100 calculados con ponderación por dificultad. El ranking es información de apoyo; la decisión de contratación corresponde exclusivamente al personal de Recursos Humanos de Grupo Ilimitado, S. A.</p>
<div class="salto"></div>
<h1>Gráficos de radar por aspirante</h1>
@foreach($filas as $f)
    @isset($graficos[$f['id_usuario']])
        <div class="grafico"><div>{{ $f['posicion'] }}. {{ $f['nombre'] }}</div><img src="{{ $graficos[$f['id_usuario']] }}" width="320" alt="Gráfico de radar"></div>
    @endisset
@endforeach
</body>
</html>
