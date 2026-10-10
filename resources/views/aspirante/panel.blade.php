@extends('layouts.app')
@section('titulo', 'Mi panel')
@section('contenido')
<h1 class="h3 mb-1">Hola, {{ $usuario->nombre }}</h1>
<p class="text-muted">
    @if($postulacion)
        Proceso: <strong>{{ $postulacion->proceso->puesto }}</strong> · Estado: <span class="badge text-bg-secondary">{{ $postulacion->estado->etiqueta() }}</span>
    @else
        Aún no está asignado a un proceso de selección.
    @endif
</p>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h5">1. Diagnóstico inicial</h2>
                @if($pretest)
                    <p class="mb-2">Completado el {{ $pretest->finalizado_en->format('d/m/Y') }}.</p>
                    <p class="mb-3">Nivel general: <strong>{{ $perfil->nivel_general->etiqueta() }}</strong> ({{ number_format($perfil->puntaje_global, 2) }}/100)</p>
                    <a href="{{ route('aspirante.resultados', $pretest) }}" class="btn btn-outline-primary">Ver resultados</a>
                @elseif($pretestActivo)
                    <p>Tiene un diagnóstico en progreso. Puede continuar donde lo dejó.</p>
                    <a href="{{ route('aspirante.diagnostico.mostrar', $pretestActivo) }}" class="btn btn-primary">Continuar diagnóstico</a>
                @else
                    <p>Evalúe sus competencias en 5 áreas con 30 preguntas.</p>
                    <a href="{{ route('aspirante.diagnostico.instrucciones', 'pretest') }}" class="btn btn-primary">Iniciar Diagnóstico</a>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h5">2. Ruta de aprendizaje</h2>
                @if($ruta)
                    <p class="mb-1">{{ $ruta->modulos->count() }} módulos asignados.</p>
                    <div class="progress mb-2" role="progressbar" aria-label="Avance de la ruta" aria-valuenow="{{ $ruta->porcentajeAvance() }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: {{ $ruta->porcentajeAvance() }}%">{{ number_format($ruta->porcentajeAvance(), 0) }} %</div>
                    </div>
                    <a href="{{ route('aspirante.ruta') }}" class="btn btn-primary">Ver mi ruta de aprendizaje</a>
                @elseif($perfil)
                    <p>Su ruta se está generando. Actualice la página en unos segundos.</p>
                @else
                    <p class="text-muted">Disponible al completar el diagnóstico.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h5">3. Diagnóstico final</h2>
                @if($postest)
                    <p>Completado el {{ $postest->finalizado_en->format('d/m/Y') }}.</p>
                    <a href="{{ route('aspirante.resultados', $postest) }}" class="btn btn-outline-primary">Ver resultados</a>
                @elseif($postestActivo)
                    <a href="{{ route('aspirante.diagnostico.mostrar', $postestActivo) }}" class="btn btn-primary">Continuar diagnóstico final</a>
                @elseif($postestHabilitado)
                    <p>Completó su ruta. Mida su avance con el diagnóstico final.</p>
                    <a href="{{ route('aspirante.diagnostico.instrucciones', 'postest') }}" class="btn btn-primary">Iniciar diagnóstico final</a>
                @else
                    <p class="text-muted">Se habilita al completar todos los módulos de su ruta.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<section class="mt-4" aria-labelledby="titulo-mensajes">
    <h2 id="titulo-mensajes" class="h5">Mensajes y avisos</h2>
    @forelse($mensajes as $mensaje)
        <div class="border rounded bg-white p-2 mb-2 small">
            <span class="text-muted">{{ $mensaje->created_at->format('d/m/Y H:i') }} ·</span>
            @switch($mensaje->data['tipo'] ?? '')
                @case('mensaje_interno') {{ $mensaje->data['mensaje'] }} @break
                @case('cambio_estado') Su estado en el proceso fue actualizado a: {{ \App\Enums\EstadoPostulacion::from($mensaje->data['estado'])->etiqueta() }}. @break
                @case('invitacion_diagnostico') Recursos Humanos le invita a completar el diagnóstico. @break
                @default Aviso de la plataforma.
            @endswitch
        </div>
    @empty
        <p class="text-muted small">No tiene mensajes.</p>
    @endforelse
</section>
@endsection
