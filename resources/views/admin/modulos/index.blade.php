@extends('layouts.app')
@section('titulo', 'Módulos formativos')
@section('contenido')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Módulos formativos</h1><a href="{{ route('admin.modulos.create') }}" class="btn btn-primary">Nuevo módulo</a></div>
<table class="table bg-white align-middle">
    <thead class="table-light"><tr><th scope="col">Código</th><th scope="col">Módulo</th><th scope="col">Área</th><th scope="col">Nivel</th><th scope="col">Recursos activos</th><th scope="col">Preguntas de evaluación</th><th scope="col">Estado</th><th scope="col"></th></tr></thead>
    <tbody>@foreach($modulos as $m)
        <tr>
            <td>{{ $m->codigo }}</td><td>{{ $m->nombre }}</td><td>{{ $m->area->nombre }}</td><td>{{ $m->nivel->etiqueta() }}</td>
            <td><span class="badge text-bg-{{ $m->recursos_activos > 0 ? 'success' : 'danger' }}">{{ $m->recursos_activos }}</span></td>
            <td><span class="badge text-bg-{{ $m->preguntas_evaluacion_count > 0 ? 'success' : 'danger' }}">{{ $m->preguntas_evaluacion_count }}</span></td>
            <td>{{ $m->activo ? 'Activo' : 'Inactivo' }}</td>
            <td class="text-nowrap"><a href="{{ route('admin.modulos.edit', $m) }}" class="btn btn-sm btn-outline-primary">Editar</a> <a href="{{ route('admin.recursos.index', ['id_modulo' => $m->id_modulo]) }}" class="btn btn-sm btn-outline-secondary">Recursos</a></td>
        </tr>
    @endforeach</tbody>
</table>
@endsection
