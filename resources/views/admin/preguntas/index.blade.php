@extends('layouts.app')
@section('titulo', 'Banco de preguntas')
@section('contenido')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Banco de preguntas</h1><a href="{{ route('admin.preguntas.create') }}" class="btn btn-primary">Nueva pregunta</a></div>
<form method="GET" class="row g-2 align-items-end bg-white p-3 rounded shadow-sm mb-3">
    <div class="col-md-3"><label class="form-label small" for="uso">Uso</label><select id="uso" name="uso" class="form-select"><option value="">Todos</option>@foreach(\App\Enums\UsoPregunta::cases() as $u)<option value="{{ $u->value }}" @selected(($filtros['uso'] ?? '') === $u->value)>{{ $u->etiqueta() }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label small" for="id_area">Área</label><select id="id_area" name="id_area" class="form-select"><option value="">Todas</option>@foreach($areas as $a)<option value="{{ $a->id_area }}" @selected(($filtros['id_area'] ?? '') == $a->id_area)>{{ $a->nombre }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label small" for="dificultad">Dificultad</label><select id="dificultad" name="dificultad" class="form-select"><option value="">Todas</option>@foreach([1 => 'Baja', 2 => 'Media', 3 => 'Alta'] as $v => $t)<option value="{{ $v }}" @selected(($filtros['dificultad'] ?? '') == $v)>{{ $t }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Filtrar</button></div>
</form>
<table class="table bg-white align-middle">
    <thead class="table-light"><tr><th scope="col">Enunciado</th><th scope="col">Área</th><th scope="col">Uso</th><th scope="col">Dificultad</th><th scope="col">Versión</th><th scope="col">Estado</th><th scope="col"></th></tr></thead>
    <tbody>@foreach($preguntas as $p)
        <tr>
            <td>{{ \Illuminate\Support\Str::limit($p->enunciado, 90) }}@if($p->modulo)<br><span class="small text-muted">{{ $p->modulo->nombre }}</span>@endif</td>
            <td>{{ $p->area->nombre }}</td><td>{{ $p->uso->etiqueta() }}</td><td>{{ [1 => 'Baja', 2 => 'Media', 3 => 'Alta'][$p->dificultad] }}</td><td>v{{ $p->version }}</td>
            <td>{{ $p->activo ? 'Activa' : 'Inactiva' }}</td>
            <td class="text-nowrap"><a href="{{ route('admin.preguntas.edit', $p) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                <form method="POST" action="{{ route('admin.preguntas.estado', $p) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary" type="submit">{{ $p->activo ? 'Desactivar' : 'Activar' }}</button></form></td>
        </tr>
    @endforeach</tbody>
</table>
{{ $preguntas->links() }}
@endsection
