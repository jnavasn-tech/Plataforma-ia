@extends('layouts.app')
@section('titulo', 'Panel administrativo')
@section('contenido')
@php($sinDatos = 'Sin datos')
<h1 class="h3 mb-3">Panel administrativo</h1>
<div class="row row-cols-2 row-cols-lg-4 g-3 mb-4">
    <div class="col"><div class="card shadow-sm h-100"><div class="card-body"><div class="small text-muted">Aspirantes registrados</div><div class="fs-3 fw-semibold">{{ $metricas['aspirantes'] }}</div></div></div></div>
    <div class="col"><div class="card shadow-sm h-100"><div class="card-body"><div class="small text-muted">Diagnósticos completados</div><div class="fs-3 fw-semibold">{{ $metricas['diagnosticos']['pretest_completados'] }}</div><div class="small">En progreso: {{ $metricas['diagnosticos']['en_progreso'] }} · Abandonados: {{ $metricas['diagnosticos']['abandonados'] }}</div></div></div></div>
    <div class="col"><div class="card shadow-sm h-100"><div class="card-body"><div class="small text-muted">Rutas generadas</div><div class="fs-3 fw-semibold">{{ $metricas['rutas']['generadas'] }}</div><div class="small">IA: {{ $metricas['rutas']['por_ia'] }} · Reglas: {{ $metricas['rutas']['por_reglas'] }}</div></div></div></div>
    <div class="col"><div class="card shadow-sm h-100"><div class="card-body"><div class="small text-muted">Tasa de retención</div><div class="fs-3 fw-semibold">{{ $metricas['rutas']['tasa_retencion'] !== null ? number_format($metricas['rutas']['tasa_retencion'], 2).' %' : $sinDatos }}</div><div class="small">Rutas completadas: {{ $metricas['rutas']['completadas'] }}</div></div></div></div>
</div>

<h2 class="h5">Indicadores del proyecto</h2>
<div class="table-responsive mb-4">
<table class="table table-bordered bg-white">
    <caption class="small">Valores calculados exclusivamente con los datos registrados en la plataforma.</caption>
    <thead class="table-light"><tr><th scope="col">Indicador</th><th scope="col">Valor actual</th><th scope="col">n</th><th scope="col">Criterio (Capítulo I)</th></tr></thead>
    <tbody>
        <tr><th scope="row">Tiempo de servidor por reactivo (promedio / p95)</th><td>{{ $metricas['tiempo_reactivo']['promedio_ms'] !== null ? number_format($metricas['tiempo_reactivo']['promedio_ms'], 0).' ms / '.$metricas['tiempo_reactivo']['p95_ms'].' ms' : $sinDatos }}</td><td>{{ $metricas['tiempo_reactivo']['n'] }}</td><td>Óptimo &lt; 5 s; aceptable 5-15 s</td></tr>
        <tr><th scope="row">Reactivos procesados en menos de 5 s</th><td>{{ $metricas['tiempo_reactivo']['porcentaje_bajo_5s'] !== null ? number_format($metricas['tiempo_reactivo']['porcentaje_bajo_5s'], 2).' %' : $sinDatos }}</td><td>{{ $metricas['tiempo_reactivo']['n'] }}</td><td>RNF-02</td></tr>
        <tr><th scope="row">Precisión frente a evaluación de control</th><td>{{ $metricas['precision']['porcentaje'] !== null ? number_format($metricas['precision']['porcentaje'], 2).' %' : $sinDatos }}</td><td>{{ $metricas['precision']['n'] }}</td><td>Meta objetivo b) ≥ 80 %; alta ≥ 85 %</td></tr>
        <tr><th scope="row">Mejora pretest-postest (promedio)</th><td>{{ $metricas['mejora']['promedio_pp'] !== null ? number_format($metricas['mejora']['promedio_pp'], 2).' pp' : $sinDatos }}</td><td>{{ $metricas['mejora']['n'] }}</td><td>Significativa ≥ 20 pp</td></tr>
        <tr><th scope="row">Satisfacción promedio por módulo (1-5)</th><td>{{ $metricas['satisfaccion']['promedio'] ?? $sinDatos }}</td><td>{{ $metricas['satisfaccion']['n'] }}</td><td>Opinión al final de cada módulo</td></tr>
        <tr><th scope="row">Solicitudes a IA con fallback</th><td>{{ $metricas['ia']['porcentaje_fallback'] !== null ? number_format($metricas['ia']['porcentaje_fallback'], 2).' %' : $sinDatos }}</td><td>{{ $metricas['ia']['total'] }}</td><td>Latencia IA promedio: {{ $metricas['ia']['latencia_promedio_ms'] !== null ? number_format($metricas['ia']['latencia_promedio_ms'], 0).' ms' : $sinDatos }}</td></tr>
    </tbody>
</table>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <h2 class="h5">Banco de preguntas de diagnóstico</h2>
        <ul class="list-group">
            @foreach($banco as $fila)
                <li class="list-group-item d-flex justify-content-between">{{ $fila['area'] }} <span class="badge text-bg-{{ $fila['disponibles'] >= $fila['minimo'] ? 'success' : 'danger' }}">{{ $fila['disponibles'] }} activas (mínimo {{ $fila['minimo'] }})</span></li>
            @endforeach
        </ul>
    </div>
    <div class="col-lg-6">
        <h2 class="h5">Alertas recientes</h2>
        <ul class="list-group">
            @forelse($notificaciones as $n)
                <li class="list-group-item small">
                    {{ $n->created_at->format('d/m/Y H:i') }} ·
                    @switch($n->data['tipo'] ?? '')
                        @case('ruta_completada') Un aspirante completó su ruta y está listo para la evaluación final. <a href="{{ route('admin.aspirantes.show', $n->data['id_usuario']) }}">Ver</a> @break
                        @case('perfil_maximo') Un aspirante alcanzó el perfil máximo en todas las áreas. <a href="{{ route('admin.aspirantes.show', $n->data['id_usuario']) }}">Ver</a> @break
                        @case('recurso_no_disponible') Recurso reportado como no disponible: {{ $n->data['titulo'] }}. <a href="{{ route('admin.recursos.edit', $n->data['id_recurso']) }}">Actualizar</a> @break
                        @default Aviso del sistema.
                    @endswitch
                </li>
            @empty
                <li class="list-group-item small text-muted">Sin alertas pendientes.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
