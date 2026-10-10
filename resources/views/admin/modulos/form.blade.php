@extends('layouts.app')
@section('titulo', $modulo->exists ? 'Editar módulo' : 'Nuevo módulo')
@section('contenido')
<h1 class="h3">{{ $modulo->exists ? 'Editar módulo' : 'Nuevo módulo' }}</h1>
<form method="POST" action="{{ $modulo->exists ? route('admin.modulos.update', $modulo) : route('admin.modulos.store') }}" class="card card-body shadow-sm">
    @csrf
    @if($modulo->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="id_area">Área</label><select id="id_area" name="id_area" class="form-select" required>@foreach($areas as $a)<option value="{{ $a->id_area }}" @selected(old('id_area', $modulo->id_area) == $a->id_area)>{{ $a->nombre }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="codigo">Código</label><input id="codigo" name="codigo" class="form-control" required maxlength="20" value="{{ old('codigo', $modulo->codigo) }}"></div>
        <div class="col-md-2"><label class="form-label" for="nivel">Nivel</label><select id="nivel" name="nivel" class="form-select">@foreach($niveles as $n)<option value="{{ $n->value }}" @selected(old('nivel', $modulo->nivel?->value) === $n->value)>{{ $n->etiqueta() }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label" for="orden">Orden</label><input id="orden" type="number" min="1" max="99" name="orden" class="form-control" value="{{ old('orden', $modulo->orden) }}"></div>
        <div class="col-12"><label class="form-label" for="nombre">Nombre</label><input id="nombre" name="nombre" class="form-control" required maxlength="150" value="{{ old('nombre', $modulo->nombre) }}"></div>
        <div class="col-12"><label class="form-label" for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" class="form-control" rows="3" maxlength="2000">{{ old('descripcion', $modulo->descripcion) }}</textarea></div>
        <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="activo" value="1" id="activo" @checked(old('activo', $modulo->activo))><label class="form-check-label" for="activo">Módulo activo (disponible para nuevas rutas)</label></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary" type="submit">Guardar</button> <a href="{{ route('admin.modulos.index') }}" class="btn btn-link">Cancelar</a></div>
</form>
@endsection
