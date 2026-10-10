@extends('layouts.app')
@section('titulo', 'Detalle del aspirante')
@section('contenido')
<nav aria-label="Ruta de navegación"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.aspirantes.index') }}">Aspirantes</a></li><li class="breadcrumb-item active" aria-current="page">{{ $aspirante->nombre }}</li></ol></nav>
<h1 class="h3">{{ $aspirante->nombre }}</h1>
<p class="text-muted small">Registro: {{ $aspirante->fecha_registro?->format('d/m/Y') }} · Último acceso: {{ $aspirante->ultimo_acceso_at?->format('d/m/Y H:i') ?? 'Sin registro' }} @if($aspirante->es_menor_edad)· <span class="badge text-bg-info">Menor de edad con consentimiento de tutor</span>@endif</p>
<div class="alert alert-secondary small" role="note">La información de la plataforma apoya la toma de decisiones. La decisión sobre el estado del aspirante corresponde exclusivamente al personal de Recursos Humanos.</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-4"><div class="card-body">
            <h2 class="h5">Perfil de competencias</h2>
            @if($perfil)
                <p>Nivel general: <strong>{{ $perfil->nivel_general->etiqueta() }}</strong> · Puntaje global: <strong>{{ number_format($perfil->puntaje_global, 2) }}</strong> @if($perfilPostest) · Postest: <strong>{{ number_format($perfilPostest->puntaje_global, 2) }}</strong> (mejora {{ number_format($perfilPostest->puntaje_global - $perfil->puntaje_global, 2) }} pp) @endif</p>
                @include('partials.tabla-areas', ['perfil' => $perfil])
                @include('partials.radar', ['grafico' => $grafico])
                <form method="POST" action="{{ route('admin.perfiles.control', $perfil) }}" class="row g-2 align-items-end mt-2">
                    @csrf @method('PUT')
                    <div class="col-auto"><label for="nivel_control" class="form-label small">Nivel según evaluación de control</label>
                        <select id="nivel_control" name="nivel_control" class="form-select form-select-sm" required><option value="" @selected($perfil->nivel_control === null) disabled>Seleccione…</option>@foreach($niveles as $n)<option value="{{ $n->value }}" @selected($perfil->nivel_control === $n)>{{ $n->etiqueta() }}</option>@endforeach</select></div>
                    <div class="col-auto"><button class="btn btn-sm btn-outline-secondary" type="submit">Registrar</button></div>
                    @if($perfil->nivel_control)<div class="col-12 small text-muted">Registrado: {{ $perfil->nivel_control->etiqueta() }} ({{ $perfil->nivel_control === $perfil->nivel_general ? 'coincide' : 'no coincide' }} con el diagnóstico)</div>@endif
                </form>
            @else
                <p>Este aspirante no ha completado el diagnóstico. Puede reenviar la invitación desde aquí.</p>
                <form method="POST" action="{{ route('admin.aspirantes.invitacion', $aspirante) }}">@csrf<button class="btn btn-outline-primary" type="submit">Reenviar invitación</button></form>
            @endif
        </div></div>

        @if($ruta)
        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h5">Progreso en la ruta ({{ number_format($ruta->porcentajeAvance(), 0) }} %)</h2>
            <p class="small text-muted">{{ $ruta->origen->etiqueta() }} · versión de prompt: {{ $ruta->version_prompt ?? 'N/A' }}</p>
            <table class="table table-sm"><thead><tr><th scope="col">Módulo</th><th scope="col">Estado</th><th scope="col">Intentos</th><th scope="col" class="text-end">Mejor nota</th></tr></thead><tbody>
                @foreach($ruta->modulos as $rm)
                    <tr><td>{{ $rm->modulo->nombre }}</td><td>{{ $rm->estado->etiqueta() }}</td><td>{{ $rm->evaluaciones->count() }}</td><td class="text-end">{{ $rm->evaluaciones->isNotEmpty() ? number_format($rm->evaluaciones->max('calificacion'), 2) : '—' }}</td></tr>
                @endforeach
            </tbody></table>
        </div></div>
        @endif
    </div>

    <div class="col-lg-5">
        @foreach($aspirante->postulaciones as $postulacion)
        <div class="card shadow-sm mb-3"><div class="card-body">
            <h2 class="h6">Proceso: {{ $postulacion->proceso->puesto }}</h2>
            <form method="POST" action="{{ route('admin.aspirantes.estado', [$aspirante, $postulacion]) }}">
                @csrf @method('PUT')
                <label for="estado-{{ $postulacion->id_postulacion }}" class="form-label small">Estado</label>
                <select id="estado-{{ $postulacion->id_postulacion }}" name="estado" class="form-select mb-2">@foreach($estados as $e)<option value="{{ $e->value }}" @selected($postulacion->estado === $e)>{{ $e->etiqueta() }}</option>@endforeach</select>
                <label for="comentario-{{ $postulacion->id_postulacion }}" class="form-label small">Comentario (opcional)</label>
                <textarea id="comentario-{{ $postulacion->id_postulacion }}" name="comentario" class="form-control mb-2" rows="2" maxlength="500"></textarea>
                <button class="btn btn-primary btn-sm" type="submit">Actualizar estado</button>
            </form>
            <h3 class="h6 mt-3">Historial</h3>
            <ul class="small mb-0">
                @forelse($postulacion->historial as $h)
                    <li>{{ $h->fecha?->format('d/m/Y H:i') }}: {{ $h->estado_anterior?->etiqueta() ?? '—' }} → {{ $h->estado_nuevo->etiqueta() }} ({{ $h->administrador?->nombre ?? 'Sistema' }})@if($h->comentario) · {{ $h->comentario }}@endif</li>
                @empty
                    <li class="text-muted">Sin cambios registrados.</li>
                @endforelse
            </ul>
        </div></div>
        @endforeach

        <div class="card shadow-sm mb-3"><div class="card-body">
            <h2 class="h6">Asignar a proceso</h2>
            <form method="POST" action="{{ route('admin.aspirantes.proceso', $aspirante) }}" class="d-flex gap-2">
                @csrf
                <label for="id_proceso" class="visually-hidden">Proceso</label>
                <select id="id_proceso" name="id_proceso" class="form-select form-select-sm">@foreach($procesos as $p)<option value="{{ $p->id_proceso }}">{{ $p->puesto }}</option>@endforeach</select>
                <button class="btn btn-sm btn-outline-primary" type="submit">Asignar</button>
            </form>
        </div></div>

        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h6">Mensaje interno</h2>
            <form method="POST" action="{{ route('admin.aspirantes.mensaje', $aspirante) }}">
                @csrf
                <label for="mensaje" class="visually-hidden">Mensaje</label>
                <textarea id="mensaje" name="mensaje" class="form-control mb-2" rows="3" maxlength="1000" required></textarea>
                <button class="btn btn-sm btn-outline-primary" type="submit">Enviar mensaje</button>
            </form>
        </div></div>
    </div>
</div>
@endsection
