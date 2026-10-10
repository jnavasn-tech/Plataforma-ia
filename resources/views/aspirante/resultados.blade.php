@extends('layouts.app')
@section('titulo', 'Resultados del diagnóstico')
@section('contenido')
<h1 class="h3">Resultados: {{ $diagnostico->tipo->etiqueta() }}</h1>
<p class="text-muted">Finalizado el {{ $diagnostico->finalizado_en->format('d/m/Y H:i') }}</p>
<div class="row g-4">
    <div class="col-lg-6">
        {{-- Pantalla 9: resultados y perfil de competencias --}}
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Perfil de competencias</h2>
                <p class="fs-5 mb-2">Nivel general: <strong>{{ $perfil->nivel_general->etiqueta() }}</strong> · Puntaje global: <strong>{{ number_format($perfil->puntaje_global, 2) }}</strong>/100</p>
                @include('partials.tabla-areas', ['perfil' => $perfil] + ($comparacion ? ['comparacion' => $comparacion] : []))
                @if($perfil->resumen)
                    <h3 class="h6 mt-3">Interpretación</h3>
                    <p>{{ $perfil->resumen }}</p>
                    @if($perfil->recomendacion)<p class="mb-1"><strong>Recomendación:</strong> {{ $perfil->recomendacion }}</p>@endif
                    <p class="small text-muted mb-0">{{ $perfil->origen_interpretacion?->etiqueta() }}. Los puntajes se calculan con reglas verificables; la interpretación es orientativa.</p>
                @else
                    <p class="text-muted small">Preparando la interpretación de su perfil…</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        {{-- Pantalla 10: gráfico de resultados --}}
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Gráfico de resultados</h2>
                @include('partials.radar', ['grafico' => $grafico, 'leyenda' => $comparacion ? 'Área azul: resultado actual. Línea discontinua: diagnóstico inicial.' : 'Puntaje por área (0 a 100).'])
            </div>
        </div>
    </div>
</div>
@if($diagnostico->tipo === \App\Enums\TipoDiagnostico::Pretest)
    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h5">Ruta de aprendizaje</h2>
            @if($ruta)
                <p>{{ $ruta->narrativa }}</p>
                <a href="{{ route('aspirante.ruta') }}" class="btn btn-primary">Ver mi ruta de aprendizaje</a>
            @else
                <p role="status"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Estamos generando su ruta personalizada. Esta página se actualizará automáticamente.</p>
            @endif
        </div>
    </div>
@endif
@endsection
