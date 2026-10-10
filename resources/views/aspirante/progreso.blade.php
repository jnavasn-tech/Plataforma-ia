@extends('layouts.app')
@section('titulo', 'Mi progreso')
@section('contenido')
<h1 class="h3">Mi progreso</h1>
@if(!$pretest)
    <div class="alert alert-info">Complete el diagnóstico inicial para ver su progreso.</div>
@else
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h2 class="h5">Resultados del diagnóstico</h2>
            <p class="mb-1">Inicial: <strong>{{ number_format($pretest->puntaje_global, 2) }}</strong> ({{ $pretest->nivel_general->etiqueta() }})</p>
            @if($postest)
                <p class="mb-1">Final: <strong>{{ number_format($postest->puntaje_global, 2) }}</strong> ({{ $postest->nivel_general->etiqueta() }})</p>
                <p>Mejora: <strong>{{ number_format($postest->puntaje_global - $pretest->puntaje_global, 2) }}</strong> puntos porcentuales</p>
            @endif
            @include('partials.radar', ['grafico' => $grafico, 'leyenda' => $postest ? 'Azul: diagnóstico inicial. Línea discontinua: diagnóstico final.' : 'Puntaje por área del diagnóstico inicial.'])
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h2 class="h5">Ruta de aprendizaje</h2>
            <div class="progress mb-3" role="progressbar" aria-label="Avance de la ruta" aria-valuenow="{{ $avance }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-success" style="width: {{ $avance }}%">{{ number_format($avance, 0) }} %</div></div>
            <table class="table table-sm">
                <caption class="visually-hidden">Estado de cada módulo y mejor calificación</caption>
                <thead><tr><th scope="col">Módulo</th><th scope="col">Estado</th><th scope="col" class="text-end">Mejor nota</th></tr></thead>
                <tbody>
                @foreach($modulos as $rm)
                    <tr><td>{{ $rm->modulo->nombre }}</td><td>{{ $rm->estado->etiqueta() }}</td><td class="text-end">{{ $rm->evaluaciones->isNotEmpty() ? number_format($rm->evaluaciones->max('calificacion'), 2) : '—' }}</td></tr>
                @endforeach
                </tbody>
            </table>
            @if($formativos)
                <h3 class="h6">Puntaje formativo por área</h3>
                <ul class="small mb-0">@foreach($formativos as $area => $nota)<li>{{ $area }}: {{ number_format($nota, 2) }}</li>@endforeach</ul>
            @endif
        </div></div>
    </div>
</div>
@endif
@endsection
