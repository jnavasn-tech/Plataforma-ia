@extends('layouts.app')
@section('titulo', 'Bitácora')
@section('contenido')
<h1 class="h3">Bitácora</h1>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link @if($vista === 'actividad') active @endif" @if($vista === 'actividad') aria-current="page" @endif href="{{ route('admin.bitacora.index', ['vista' => 'actividad']) }}">Actividad</a></li>
    <li class="nav-item"><a class="nav-link @if($vista === 'ia') active @endif" @if($vista === 'ia') aria-current="page" @endif href="{{ route('admin.bitacora.index', ['vista' => 'ia']) }}">Solicitudes de IA</a></li>
</ul>
<form method="GET" class="row g-2 align-items-end bg-white p-3 rounded shadow-sm mb-3">
    <input type="hidden" name="vista" value="{{ $vista }}">
    @if($vista === 'actividad')
        <div class="col-md-4"><label class="form-label small" for="accion">Acción</label><select id="accion" name="accion" class="form-select"><option value="">Todas</option>@foreach($acciones as $a)<option value="{{ $a }}" @selected(($filtros['accion'] ?? '') === $a)>{{ $a }}</option>@endforeach</select></div>
    @else
        <div class="col-md-3"><label class="form-label small" for="tipo">Tipo</label><select id="tipo" name="tipo" class="form-select"><option value="">Todos</option>@foreach(['diagnostico', 'ruta', 'retroalimentacion'] as $t)<option value="{{ $t }}" @selected(($filtros['tipo'] ?? '') === $t)>{{ $t }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small" for="estado">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos</option>@foreach(['exito', 'fallback', 'deshabilitada'] as $e)<option value="{{ $e }}" @selected(($filtros['estado'] ?? '') === $e)>{{ $e }}</option>@endforeach</select></div>
    @endif
    <div class="col-md-2"><label class="form-label small" for="desde">Desde</label><input id="desde" type="date" name="desde" class="form-control" value="{{ $filtros['desde'] ?? '' }}"></div>
    <div class="col-md-2"><label class="form-label small" for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" class="form-control" value="{{ $filtros['hasta'] ?? '' }}"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Filtrar</button></div>
</form>
<div class="table-responsive">
@if($vista === 'actividad')
    <table class="table table-sm bg-white">
        <thead class="table-light"><tr><th scope="col">Fecha</th><th scope="col">Usuario</th><th scope="col">Acción</th><th scope="col">Entidad</th><th scope="col">Detalle</th><th scope="col">IP (anonimizada)</th></tr></thead>
        <tbody>@forelse($registros as $r)
            <tr><td>{{ $r->fecha?->format('d/m/Y H:i:s') }}</td><td>{{ $r->usuario?->nombre ?? '—' }}</td><td>{{ $r->accion }}</td><td>{{ $r->entidad }} {{ $r->id_entidad }}</td><td class="small">{{ $r->detalle_json ? json_encode($r->detalle_json, JSON_UNESCAPED_UNICODE) : '' }}</td><td>{{ $r->ip_anonimizada }}</td></tr>
        @empty<tr><td colspan="6" class="text-muted">Sin registros.</td></tr>@endforelse</tbody>
    </table>
@else
    <table class="table table-sm bg-white">
        <thead class="table-light"><tr><th scope="col">Fecha</th><th scope="col">Tipo</th><th scope="col">Estado</th><th scope="col">Modelo</th><th scope="col">Prompt</th><th scope="col" class="text-end">Latencia (ms)</th><th scope="col">Intentos</th><th scope="col">Tokens (entrada/salida)</th><th scope="col">Código</th></tr></thead>
        <tbody>@forelse($registros as $r)
            <tr><td>{{ $r->fecha?->format('d/m/Y H:i:s') }}</td><td>{{ $r->tipo }}</td><td>{{ $r->estado->value }}</td><td>{{ $r->modelo ?? '—' }}</td><td>{{ $r->version_prompt }}</td><td class="text-end">{{ $r->latencia_ms }}</td><td>{{ $r->intentos }}</td><td>{{ $r->tokens_entrada ?? '—' }} / {{ $r->tokens_salida ?? '—' }}</td><td>{{ $r->codigo_error ?? '—' }}</td></tr>
        @empty<tr><td colspan="9" class="text-muted">Sin registros.</td></tr>@endforelse</tbody>
    </table>
@endif
</div>
{{ $registros->links() }}
@endsection
