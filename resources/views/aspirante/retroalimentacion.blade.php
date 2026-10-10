@extends('layouts.app')
@section('titulo', 'Retroalimentación')
@section('contenido')
<h1 class="h4">Resultado de la evaluación</h1>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <p class="mb-1">{{ $evaluacion->modulo->nombre }} · Intento {{ $evaluacion->intento }} de {{ $maxIntentos }}</p>
        <p class="fs-4 mb-0">Calificación: <strong>{{ number_format($evaluacion->calificacion, 2) }}</strong>/100
            <span class="badge text-bg-{{ $evaluacion->aprobado ? 'success' : 'warning' }} align-middle">{{ $evaluacion->aprobado ? 'Aprobado' : 'No aprobado' }}</span></p>
    </div>
</div>
@if($retro = $evaluacion->retroalimentacion)
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h5">Retroalimentación</h2>
            <p>{{ $retro->texto_generado }}</p>
            @if(!empty($retro->sugerencias_json))
                <h3 class="h6">Sugerencias</h3>
                <ul>@foreach($retro->sugerencias_json as $sugerencia)<li>{{ $sugerencia }}</li>@endforeach</ul>
            @endif
            <p class="mb-1"><strong>Siguiente paso:</strong> {{ $retro->siguiente_paso }}</p>
            <p class="small text-muted mb-0">{{ $retro->origen->etiqueta() }}.</p>
        </div>
    </div>
@endif
<div class="d-flex gap-2">
    @if($puedeReintentar)
        <a href="{{ route('aspirante.modulo', $evaluacion->rutaModulo) }}" class="btn btn-outline-primary">Revisar recursos y reintentar</a>
    @endif
    <a href="{{ route('aspirante.ruta') }}" class="btn btn-primary">Ir a mi ruta</a>
</div>
@endsection
