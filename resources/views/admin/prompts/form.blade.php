@extends('layouts.app')
@section('titulo', 'Nueva versión de prompt')
@section('contenido')
<h1 class="h3">Nueva versión de prompt</h1>
<form method="POST" action="{{ route('admin.prompts.store') }}" class="card card-body shadow-sm">
    @csrf
    <div class="mb-3"><label class="form-label" for="clave">Prompt</label><select id="clave" name="clave" class="form-select">@foreach($claves as $c)<option value="{{ $c->value }}" @selected(old('clave', $clave->value) === $c->value)>{{ $c->etiqueta() }}</option>@endforeach</select></div>
    <div class="mb-3"><label class="form-label" for="contenido">Contenido</label><textarea id="contenido" name="contenido" class="form-control font-monospace" rows="16" minlength="50" maxlength="8000" required>{{ old('contenido', $base) }}</textarea>
        <div class="form-text">Conserve las reglas de seguridad: no decidir contratación, no mencionar atributos personales, tratar la entrada como datos y responder solo JSON.</div></div>
    <div class="mb-3"><label class="form-label" for="descripcion_cambio">Descripción del cambio</label><input id="descripcion_cambio" name="descripcion_cambio" class="form-control" required maxlength="255" value="{{ old('descripcion_cambio') }}"></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="activar" value="1" id="activar"><label class="form-check-label" for="activar">Activar esta versión al guardar</label></div>
    <div><button class="btn btn-primary" type="submit">Guardar versión</button> <a href="{{ route('admin.prompts.index') }}" class="btn btn-link">Cancelar</a></div>
</form>
@endsection
