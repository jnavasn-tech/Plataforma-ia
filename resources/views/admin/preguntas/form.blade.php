@extends('layouts.app')
@section('titulo', $pregunta->exists ? 'Editar pregunta' : 'Nueva pregunta')
@section('contenido')
<h1 class="h3">{{ $pregunta->exists ? 'Editar pregunta' : 'Nueva pregunta' }}</h1>
@if($pregunta->exists)<p class="small text-muted">Si la pregunta ya fue utilizada en diagnósticos o evaluaciones, al guardar se creará una nueva versión y la actual quedará inactiva.</p>@endif
@php($opciones = old('opciones', $pregunta->exists ? $pregunta->opciones->pluck('texto')->all() : ['', '', '', '']))
@php($correcta = old('correcta', $pregunta->exists ? $pregunta->opciones->search(fn ($o) => $o->es_correcta) : 0))
<form method="POST" action="{{ $pregunta->exists ? route('admin.preguntas.update', $pregunta) : route('admin.preguntas.store') }}" class="card card-body shadow-sm" novalidate>
    @csrf
    @if($pregunta->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="id_area">Área</label><select id="id_area" name="id_area" class="form-select">@foreach($areas as $a)<option value="{{ $a->id_area }}" @selected(old('id_area', $pregunta->id_area) == $a->id_area)>{{ $a->nombre }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="uso">Uso</label><select id="uso" name="uso" class="form-select">@foreach($usos as $u)<option value="{{ $u->value }}" @selected(old('uso', $pregunta->uso?->value) === $u->value)>{{ $u->etiqueta() }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="dificultad">Dificultad</label><select id="dificultad" name="dificultad" class="form-select">@foreach([1 => 'Baja', 2 => 'Media', 3 => 'Alta'] as $v => $t)<option value="{{ $v }}" @selected(old('dificultad', $pregunta->dificultad) == $v)>{{ $t }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label" for="id_modulo">Módulo (solo para preguntas de evaluación)</label><select id="id_modulo" name="id_modulo" class="form-select"><option value="">No aplica</option>@foreach($modulos as $m)<option value="{{ $m->id_modulo }}" @selected(old('id_modulo', $pregunta->id_modulo) == $m->id_modulo)>{{ $m->area->nombre }} · {{ $m->nombre }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label" for="enunciado">Enunciado</label><textarea id="enunciado" name="enunciado" class="form-control" rows="3" required maxlength="1000">{{ old('enunciado', $pregunta->enunciado) }}</textarea></div>
        <fieldset class="col-12">
            <legend class="fs-6">Opciones de respuesta (marque la correcta)</legend>
            @foreach($opciones as $i => $texto)
                <div class="input-group mb-2">
                    <div class="input-group-text"><input class="form-check-input mt-0" type="radio" name="correcta" value="{{ $i }}" aria-label="Opción {{ $i + 1 }} correcta" @checked((string) $correcta === (string) $i)></div>
                    <label class="visually-hidden" for="opcion-{{ $i }}">Opción {{ $i + 1 }}</label>
                    <input id="opcion-{{ $i }}" name="opciones[]" class="form-control" maxlength="300" value="{{ $texto }}">
                </div>
            @endforeach
        </fieldset>
        <div class="col-12"><label class="form-label" for="explicacion">Explicación (para revisión de expertos)</label><textarea id="explicacion" name="explicacion" class="form-control" rows="2" maxlength="1000">{{ old('explicacion', $pregunta->explicacion) }}</textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary" type="submit">Guardar</button> <a href="{{ route('admin.preguntas.index') }}" class="btn btn-link">Cancelar</a></div>
</form>
@endsection
