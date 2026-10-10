@extends('layouts.app')
@section('titulo', 'Evaluación del módulo')
@section('contenido')
<h1 class="h4">Evaluación: {{ $rutaModulo->modulo->nombre }}</h1>
<p class="text-muted">Intento {{ $intento }} de {{ $maxIntentos }} · Calificación mínima para aprobar: {{ number_format($umbral, 0) }} %</p>
<form method="POST" action="{{ route('aspirante.evaluacion.store', $rutaModulo) }}">
    @csrf
    @foreach($preguntas as $n => $pregunta)
        <fieldset class="card card-body shadow-sm mb-3">
            <legend class="fs-6 fw-semibold">{{ $n + 1 }}. {{ $pregunta->enunciado }}</legend>
            @foreach($pregunta->opciones as $opcion)
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="respuestas[{{ $pregunta->id_pregunta }}]" id="r-{{ $opcion->id_opcion }}" value="{{ $opcion->id_opcion }}" required @checked(old('respuestas.'.$pregunta->id_pregunta) == $opcion->id_opcion)>
                    <label class="form-check-label" for="r-{{ $opcion->id_opcion }}">{{ $opcion->texto }}</label>
                </div>
            @endforeach
            @error('respuestas.'.$pregunta->id_pregunta)<div class="text-danger small">{{ $message }}</div>@enderror
        </fieldset>
    @endforeach
    <button class="btn btn-primary" type="submit">Enviar evaluación</button>
    <a href="{{ route('aspirante.modulo', $rutaModulo) }}" class="btn btn-link">Volver al módulo</a>
</form>
@endsection
