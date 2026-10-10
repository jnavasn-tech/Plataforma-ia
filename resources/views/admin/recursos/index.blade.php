@extends('layouts.app')
@section('titulo', 'Catálogo de recursos')
@section('contenido')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Catálogo de recursos</h1><a href="{{ route('admin.recursos.create') }}" class="btn btn-primary">Agregar recurso</a></div>
<form method="GET" class="row g-2 align-items-end bg-white p-3 rounded shadow-sm mb-3">
    <div class="col-md-5"><label class="form-label small" for="id_modulo">Módulo</label><select id="id_modulo" name="id_modulo" class="form-select"><option value="">Todos</option>@foreach($modulos as $m)<option value="{{ $m->id_modulo }}" @selected(($filtros['id_modulo'] ?? '') == $m->id_modulo)>{{ $m->area->nombre }} · {{ $m->nombre }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label small" for="estado">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos</option>@foreach(\App\Enums\EstadoRecurso::cases() as $e)<option value="{{ $e->value }}" @selected(($filtros['estado'] ?? '') === $e->value)>{{ $e->etiqueta() }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Filtrar</button></div>
</form>
<table class="table bg-white align-middle">
    <thead class="table-light"><tr><th scope="col">Recurso</th><th scope="col">Módulo</th><th scope="col">Tipo</th><th scope="col">Estado</th><th scope="col"></th></tr></thead>
    <tbody>@forelse($recursos as $r)
        <tr>
            <td>{{ $r->titulo }}@if($r->url)<br><span class="small text-muted text-break">{{ $r->url }}</span>@endif</td>
            <td>{{ $r->modulo->nombre }}</td><td>{{ $r->tipo->etiqueta() }}</td>
            <td><span class="badge text-bg-{{ $r->estado->value === 'activo' ? 'success' : ($r->estado->value === 'inactivo' ? 'secondary' : 'warning') }}">{{ $r->estado->etiqueta() }}</span></td>
            <td class="text-nowrap">
                <a href="{{ route('admin.recursos.edit', $r) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                <form method="POST" action="{{ route('admin.recursos.estado', $r) }}" class="d-inline">@csrf @method('PATCH')<input type="hidden" name="estado" value="{{ $r->estado->value === 'activo' ? 'inactivo' : 'activo' }}"><button class="btn btn-sm btn-outline-secondary" type="submit">{{ $r->estado->value === 'activo' ? 'Dar de baja' : 'Activar' }}</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted">No hay recursos con los filtros seleccionados.</td></tr>
    @endforelse</tbody>
</table>
{{ $recursos->links() }}
@endsection
