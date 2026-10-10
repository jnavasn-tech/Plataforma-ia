@extends('layouts.app')
@section('titulo', $recurso->exists ? 'Editar recurso' : 'Agregar recurso')
@section('contenido')
<h1 class="h3">{{ $recurso->exists ? 'Editar recurso' : 'Agregar recurso' }}</h1>
@if(session('recurso_duplicado'))
    <div class="alert alert-info"><a href="{{ route('admin.recursos.edit', session('recurso_duplicado')) }}">Editar el recurso existente</a></div>
@endif
<form method="POST" action="{{ $recurso->exists ? route('admin.recursos.update', $recurso) : route('admin.recursos.store') }}" class="card card-body shadow-sm" novalidate>
    @csrf
    @if($recurso->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="id_modulo">Módulo (determina el área de competencia)</label><select id="id_modulo" name="id_modulo" class="form-select" required>@foreach($modulos as $m)<option value="{{ $m->id_modulo }}" @selected(old('id_modulo', $recurso->id_modulo) == $m->id_modulo)>{{ $m->area->nombre }} · {{ $m->nombre }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="tipo">Tipo</label><select id="tipo" name="tipo" class="form-select">@foreach($tipos as $t)<option value="{{ $t->value }}" @selected(old('tipo', $recurso->tipo?->value) === $t->value)>{{ $t->etiqueta() }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="duracion_minutos">Duración (min)</label><input id="duracion_minutos" type="number" min="1" max="600" name="duracion_minutos" class="form-control" value="{{ old('duracion_minutos', $recurso->duracion_minutos) }}"></div>
        <div class="col-12"><label class="form-label" for="titulo">Título</label><input id="titulo" name="titulo" class="form-control @error('titulo') is-invalid @enderror" required maxlength="150" value="{{ old('titulo', $recurso->titulo) }}"></div>
        <div class="col-12"><label class="form-label" for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" class="form-control" rows="2" maxlength="2000">{{ old('descripcion', $recurso->descripcion) }}</textarea></div>
        <div class="col-12"><label class="form-label" for="url">URL del recurso externo</label><input id="url" name="url" type="url" class="form-control @error('url') is-invalid @enderror" maxlength="500" value="{{ old('url', $recurso->url) }}" aria-describedby="ayuda-url">
            <div id="ayuda-url" class="form-text">Al guardar se verifica que la URL responda. Deje vacío si es una lectura interna.</div>
            @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-12"><label class="form-label" for="contenido">Contenido de la lectura interna</label><textarea id="contenido" name="contenido" class="form-control @error('contenido') is-invalid @enderror" rows="6" maxlength="10000">{{ old('contenido', $recurso->contenido) }}</textarea>@error('contenido')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        @if($recurso->exists)
            <div class="col-md-4"><label class="form-label" for="estado">Estado</label><select id="estado" name="estado" class="form-select">@foreach($estados as $e)<option value="{{ $e->value }}" @selected(old('estado', $recurso->estado?->value) === $e->value)>{{ $e->etiqueta() }}</option>@endforeach</select></div>
        @endif
        @if(session('ofrecer_pendiente'))
            <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="guardar_pendiente" value="1" id="guardar_pendiente"><label class="form-check-label" for="guardar_pendiente">Guardar de todas formas con estado "pendiente de verificación"</label></div>
        @endif
    </div>
    <div class="mt-3"><button class="btn btn-primary" type="submit">Guardar</button> <a href="{{ route('admin.recursos.index') }}" class="btn btn-link">Cancelar</a></div>
</form>
@endsection
