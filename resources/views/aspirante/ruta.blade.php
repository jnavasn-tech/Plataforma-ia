@extends('layouts.app')
@section('titulo', 'Mi ruta de aprendizaje')
@section('contenido')
<h1 class="h3">Mi ruta de aprendizaje</h1>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <p class="mb-2">{{ $ruta->narrativa }}</p>
        <p class="small text-muted mb-2">{{ $ruta->origen->etiqueta() }} · Generada el {{ $ruta->fecha_generacion?->format('d/m/Y') }} @if($ruta->es_ruta_avanzada)· <span class="badge text-bg-success">Ruta de desarrollo avanzado opcional</span>@endif</p>
        <div class="progress" role="progressbar" aria-label="Avance de la ruta" aria-valuenow="{{ $avance }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar bg-success" style="width: {{ $avance }}%">{{ number_format($avance, 0) }} %</div>
        </div>
    </div>
</div>
<ol class="list-group">
    @foreach($modulos as $rm)
        <li class="list-group-item">
            <div class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="fw-semibold">{{ $rm->orden }}. {{ $rm->modulo->nombre }}</div>
                    <div class="small text-muted">{{ $rm->modulo->area->nombre }} · Nivel {{ mb_strtolower($rm->modulo->nivel->etiqueta()) }} · Prioridad {{ $rm->prioridad->value }}</div>
                    <div class="small">{{ $rm->justificacion }}</div>
                </div>
                <div class="col-md-3 text-md-end">
                    <span class="badge text-bg-{{ $rm->estado->value === 'completado' ? 'success' : ($rm->estado->value === 'en_curso' ? 'primary' : 'secondary') }}">{{ $rm->estado->etiqueta() }}</span>
                    <div class="mt-2">
                        @if($accesibles[$rm->id_ruta_modulo])
                            <a href="{{ route('aspirante.modulo', $rm) }}" class="btn btn-sm btn-outline-primary">{{ $rm->estado->value === 'completado' ? 'Revisar' : 'Abrir módulo' }}</a>
                        @else
                            <span class="small text-muted">Bloqueado hasta concluir el módulo anterior</span>
                        @endif
                    </div>
                </div>
            </div>
        </li>
    @endforeach
</ol>
@endsection
