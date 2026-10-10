@extends('layouts.app')
@section('titulo', $rutaModulo->modulo->nombre)
@section('contenido')
<nav aria-label="Ruta de navegación"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('aspirante.ruta') }}">Mi ruta</a></li><li class="breadcrumb-item active" aria-current="page">{{ $rutaModulo->modulo->nombre }}</li></ol></nav>
<h1 class="h3">{{ $rutaModulo->modulo->nombre }}</h1>
<p class="text-muted">{{ $rutaModulo->modulo->area->nombre }} · Nivel {{ mb_strtolower($rutaModulo->modulo->nivel->etiqueta()) }} · Avance {{ number_format($rutaModulo->porcentaje_avance, 0) }} %</p>
<p>{{ $rutaModulo->modulo->descripcion }}</p>

<h2 class="h5 mt-4">Recursos</h2>
<div class="list-group mb-4">
    @foreach($recursos as $recurso)
        @php($registro = $progresos->get($recurso->id_recurso))
        <a href="{{ route('aspirante.recurso', [$rutaModulo, $recurso]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            <span>{{ $recurso->titulo }} <small class="text-muted">({{ $recurso->tipo->etiqueta() }}@if($recurso->duracion_minutos), {{ $recurso->duracion_minutos }} min @endif)</small></span>
            <span class="badge text-bg-{{ $registro?->completado_en ? 'success' : ($registro ? 'primary' : 'secondary') }}">{{ $registro?->completado_en ? 'Completado' : ($registro ? 'Iniciado' : 'Pendiente') }}</span>
        </a>
    @endforeach
</div>

<h2 class="h5">Evaluación del módulo</h2>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <p class="small">Calificación mínima para aprobar: {{ number_format($umbral, 0) }} %. Intentos permitidos: {{ $maxIntentos }}.</p>
        @if($rutaModulo->evaluaciones->isNotEmpty())
            <ul class="small">
                @foreach($rutaModulo->evaluaciones as $evaluacion)
                    <li><a href="{{ route('aspirante.evaluacion.show', $evaluacion) }}">Intento {{ $evaluacion->intento }}</a>: {{ number_format($evaluacion->calificacion, 2) }} — {{ $evaluacion->aprobado ? 'Aprobado' : 'No aprobado' }}</li>
                @endforeach
            </ul>
        @endif
        @if($evaluacionHabilitada)
            <a href="{{ route('aspirante.evaluacion.create', $rutaModulo) }}" class="btn btn-primary">Realizar evaluación</a>
        @elseif($rutaModulo->estado->value !== 'completado')
            <p class="text-muted mb-0">Complete los {{ $pendientes }} recurso(s) pendiente(s) para habilitar la evaluación.</p>
        @else
            <p class="text-success mb-0">Módulo concluido.</p>
        @endif
    </div>
</div>

@if($rutaModulo->estado->value === 'completado')
    <h2 class="h5">Su opinión sobre el módulo</h2>
    <form method="POST" action="{{ route('aspirante.valoracion.store', $rutaModulo) }}" class="card card-body shadow-sm">
        @csrf
        <fieldset class="mb-2">
            <legend class="fs-6">¿Qué tan útil le resultó este módulo? (1 = nada útil, 5 = muy útil)</legend>
            @for($i = 1; $i <= 5; $i++)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="puntuacion" id="puntuacion-{{ $i }}" value="{{ $i }}" @checked(old('puntuacion', $rutaModulo->valoracion?->puntuacion) == $i) required>
                    <label class="form-check-label" for="puntuacion-{{ $i }}">{{ $i }}</label>
                </div>
            @endfor
        </fieldset>
        <label for="comentario" class="form-label">Comentario (opcional)</label>
        <textarea id="comentario" name="comentario" class="form-control mb-2" maxlength="500" rows="2">{{ old('comentario', $rutaModulo->valoracion?->comentario) }}</textarea>
        <div><button class="btn btn-outline-primary" type="submit">Enviar opinión</button></div>
    </form>
@endif
@endsection
