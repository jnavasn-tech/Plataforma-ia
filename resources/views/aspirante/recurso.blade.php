@extends('layouts.app')
@section('titulo', $recurso->titulo)
@section('contenido')
<nav aria-label="Ruta de navegación"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('aspirante.ruta') }}">Mi ruta</a></li><li class="breadcrumb-item"><a href="{{ route('aspirante.modulo', $rutaModulo) }}">{{ $rutaModulo->modulo->nombre }}</a></li><li class="breadcrumb-item active" aria-current="page">{{ $recurso->titulo }}</li></ol></nav>
<article class="card shadow-sm">
    <div class="card-body p-4">
        <h1 class="h4">{{ $recurso->titulo }}</h1>
        <p class="small text-muted">{{ $recurso->tipo->etiqueta() }}@if($recurso->duracion_minutos) · {{ $recurso->duracion_minutos }} minutos @endif · Iniciado el {{ $registro->iniciado_en?->format('d/m/Y H:i') }}</p>
        @if($recurso->descripcion)<p>{{ $recurso->descripcion }}</p>@endif
        @if($recurso->contenido)
            {{-- El contenido interno se muestra como texto escapado (prevención de XSS). --}}
            <div class="border-start border-3 ps-3 mb-3">{!! nl2br(e($recurso->contenido)) !!}</div>
        @endif
        @if($recurso->esExterno())
            <p><a href="{{ $recurso->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">Abrir recurso externo</a> <span class="small text-muted">(se abre en una pestaña nueva)</span></p>
        @endif
        <div class="d-flex flex-wrap gap-2 mt-3">
            @if($registro->completado_en)
                <span class="badge text-bg-success align-self-center">Completado el {{ $registro->completado_en->format('d/m/Y H:i') }}</span>
            @else
                <form method="POST" action="{{ route('aspirante.recurso.completar', [$rutaModulo, $recurso]) }}">
                    @csrf
                    <button class="btn btn-success" type="submit">Marcar como completado</button>
                </form>
            @endif
            @if($recurso->esExterno())
                <form method="POST" action="{{ route('aspirante.recurso.reportar', [$rutaModulo, $recurso]) }}">
                    @csrf
                    <button class="btn btn-outline-danger" type="submit">Reportar recurso no disponible</button>
                </form>
            @endif
        </div>
    </div>
</article>
@if($alternativas->isNotEmpty())
    <section class="mt-3" aria-labelledby="titulo-alternativas">
        <h2 id="titulo-alternativas" class="h6">Recursos alternativos</h2>
        <ul>
            @foreach($alternativas as $alternativa)
                <li>{{ $alternativa->titulo }} @if($alternativa->id_modulo === $rutaModulo->id_modulo)<a href="{{ route('aspirante.recurso', [$rutaModulo, $alternativa]) }}">Abrir</a>@else @if($alternativa->url)<a href="{{ $alternativa->url }}" target="_blank" rel="noopener noreferrer">Abrir</a>@else <span class="text-muted small">(disponible en otro módulo del área)</span>@endif @endif</li>
            @endforeach
        </ul>
    </section>
@endif
@endsection
