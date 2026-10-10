@extends('layouts.app')
@section('titulo', 'Procesos de selección')
@section('contenido')
<h1 class="h3">Procesos de selección</h1>
<form method="POST" action="{{ route('admin.procesos.store') }}" class="row g-2 align-items-end bg-white p-3 rounded shadow-sm mb-3">
    @csrf
    <div class="col-md-3"><label class="form-label small" for="nombre">Nombre</label><input id="nombre" name="nombre" class="form-control" required maxlength="120" value="{{ old('nombre') }}"></div>
    <div class="col-md-3"><label class="form-label small" for="puesto">Puesto</label><input id="puesto" name="puesto" class="form-control" required maxlength="120" value="{{ old('puesto') }}"></div>
    <div class="col-md-2"><label class="form-label small" for="fecha_inicio">Inicio</label><input id="fecha_inicio" type="date" name="fecha_inicio" class="form-control" required value="{{ old('fecha_inicio') }}"></div>
    <div class="col-md-2"><label class="form-label small" for="fecha_fin">Fin</label><input id="fecha_fin" type="date" name="fecha_fin" class="form-control" value="{{ old('fecha_fin') }}"></div>
    <div class="col-md-1 form-check ms-2"><input class="form-check-input" type="checkbox" name="activo" value="1" id="activo" checked><label class="form-check-label small" for="activo">Activo</label></div>
    <div class="col-md-1"><button class="btn btn-primary" type="submit">Crear</button></div>
</form>
<table class="table bg-white">
    <thead class="table-light"><tr><th scope="col">Proceso</th><th scope="col">Puesto</th><th scope="col">Vigencia</th><th scope="col">Aspirantes</th><th scope="col">Estado</th></tr></thead>
    <tbody>@foreach($procesos as $p)<tr><td>{{ $p->nombre }}</td><td>{{ $p->puesto }}</td><td>{{ $p->fecha_inicio->format('d/m/Y') }} – {{ $p->fecha_fin?->format('d/m/Y') ?? 'abierto' }}</td><td>{{ $p->postulaciones_count }}</td><td>{{ $p->activo ? 'Activo' : 'Inactivo' }}</td></tr>@endforeach</tbody>
</table>
@endsection
