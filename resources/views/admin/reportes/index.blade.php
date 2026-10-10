@extends('layouts.app')
@section('titulo', 'Reportes de competencias')
@section('contenido')
<h1 class="h3">Reportes de competencias</h1>
<form method="GET" action="{{ route('admin.reportes.index') }}" class="row g-2 align-items-end bg-white p-3 rounded shadow-sm mb-3">
    <input type="hidden" name="consultar" value="1">
    <div class="col-md-2"><label class="form-label small" for="tipo">Tipo</label><select id="tipo" name="tipo" class="form-select"><option value="comparativo" @selected(($filtros['tipo'] ?? '') !== 'individual')>Comparativo</option><option value="individual" @selected(($filtros['tipo'] ?? '') === 'individual')>Individual</option></select></div>
    <div class="col-md-3"><label class="form-label small" for="id_proceso">Proceso de selección</label><select id="id_proceso" name="id_proceso" class="form-select"><option value="">Todos</option>@foreach($procesos as $p)<option value="{{ $p->id_proceso }}" @selected(($filtros['id_proceso'] ?? '') == $p->id_proceso)>{{ $p->puesto }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label small" for="id_usuario">Aspirante (reporte individual)</label><select id="id_usuario" name="id_usuario" class="form-select"><option value="">—</option>@foreach($aspirantes as $a)<option value="{{ $a->id_usuario }}" @selected(($filtros['id_usuario'] ?? '') == $a->id_usuario)>{{ $a->nombre }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label small" for="desde">Desde</label><input id="desde" type="date" name="desde" class="form-control" value="{{ $filtros['desde'] ?? '' }}"></div>
    <div class="col-md-2"><label class="form-label small" for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" class="form-control" value="{{ $filtros['hasta'] ?? '' }}"></div>
    <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Consultar</button>
        @if($filas && $filas->isNotEmpty())
            <a class="btn btn-outline-danger" href="{{ route('admin.reportes.exportar', array_merge($filtros, ['formato' => 'pdf'])) }}">Exportar PDF</a>
            <a class="btn btn-outline-success" href="{{ route('admin.reportes.exportar', array_merge($filtros, ['formato' => 'xlsx'])) }}">Exportar Excel</a>
        @endif
    </div>
</form>
@if($sinDatos)
    <div class="alert alert-info" role="status">{{ $sinDatos }}</div>
@elseif($filas)
    <p class="small text-muted">El ranking ordena por el puntaje global más reciente (postest si existe; de lo contrario, pretest). Es información de apoyo: la decisión de contratación corresponde a RRHH.</p>
    <div class="table-responsive">
    <table class="table table-sm bg-white">
        <thead class="table-light"><tr><th scope="col">#</th><th scope="col">Aspirante</th><th scope="col">Puesto</th><th scope="col">Estado</th><th scope="col" class="text-end">Pretest</th><th scope="col">Nivel</th><th scope="col" class="text-end">Postest</th><th scope="col" class="text-end">Mejora (pp)</th><th scope="col" class="text-end">Avance ruta</th></tr></thead>
        <tbody>@foreach($filas as $f)
            <tr><td>{{ $f['posicion'] }}</td><td>{{ $f['nombre'] }}</td><td>{{ $f['puesto'] }}</td><td>{{ $f['estado'] }}</td><td class="text-end">{{ number_format($f['pretest'], 2) }}</td><td>{{ $f['nivel'] }}</td><td class="text-end">{{ $f['postest'] !== null ? number_format($f['postest'], 2) : '—' }}</td><td class="text-end">{{ $f['mejora'] !== null ? number_format($f['mejora'], 2) : '—' }}</td><td class="text-end">{{ number_format($f['avance_ruta'], 0) }} %</td></tr>
        @endforeach</tbody>
    </table>
    </div>
    <h2 class="h5 mt-3">Gráficos de radar por aspirante (primeros 10)</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
        @foreach($filas->take(10) as $f)
            @isset($graficos[$f['id_usuario']])
                <div class="col"><div class="card card-body text-center"><p class="small fw-semibold mb-1">{{ $f['posicion'] }}. {{ $f['nombre'] }}</p><img src="{{ $graficos[$f['id_usuario']] }}" alt="Gráfico de radar del perfil de {{ $f['nombre'] }}" class="img-fluid"></div></div>
            @endisset
        @endforeach
    </div>
@endif
@endsection
