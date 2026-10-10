@extends('layouts.app')
@section('titulo', 'Gestión de aspirantes')
@section('contenido')
<h1 class="h3">Gestión de aspirantes</h1>
<form method="GET" class="row g-2 align-items-end mb-3 bg-white p-3 rounded shadow-sm" aria-label="Filtros de aspirantes">
    <div class="col-md-3"><label class="form-label small" for="buscar">Nombre</label><input id="buscar" name="buscar" class="form-control" value="{{ $filtros['buscar'] ?? '' }}"></div>
    <div class="col-md-2"><label class="form-label small" for="estado">Estado</label>
        <select id="estado" name="estado" class="form-select"><option value="">Todos</option>@foreach(\App\Enums\EstadoPostulacion::cases() as $e)<option value="{{ $e->value }}" @selected(($filtros['estado'] ?? '') === $e->value)>{{ $e->etiqueta() }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label small" for="id_proceso">Proceso</label>
        <select id="id_proceso" name="id_proceso" class="form-select"><option value="">Todos</option>@foreach($procesos as $p)<option value="{{ $p->id_proceso }}" @selected(($filtros['id_proceso'] ?? '') == $p->id_proceso)>{{ $p->puesto }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label small" for="desde">Registro desde</label><input id="desde" type="date" name="desde" class="form-control" value="{{ $filtros['desde'] ?? '' }}"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Filtrar</button></div>
</form>
<div class="table-responsive">
<table class="table table-hover bg-white align-middle">
    <thead class="table-light"><tr><th scope="col">Aspirante</th><th scope="col">Registro</th><th scope="col">Proceso / estado</th><th scope="col" class="text-end">Puntaje inicial</th><th scope="col">Nivel</th><th scope="col"></th></tr></thead>
    <tbody>
    @forelse($aspirantes as $a)
        @php($postulacion = $a->postulaciones->sortByDesc('created_at')->first())
        @php($perfil = $a->diagnosticos->where('tipo', \App\Enums\TipoDiagnostico::Pretest)->sortByDesc('finalizado_en')->first()?->perfil)
        <tr>
            <td>{{ $a->nombre }}</td>
            <td>{{ $a->fecha_registro?->format('d/m/Y') }}</td>
            <td>{{ $postulacion?->proceso?->puesto ?? '—' }} <span class="badge text-bg-secondary">{{ $postulacion?->estado?->etiqueta() ?? 'Sin proceso' }}</span></td>
            <td class="text-end">{{ $perfil ? number_format($perfil->puntaje_global, 2) : 'Sin diagnóstico' }}</td>
            <td>{{ $perfil?->nivel_general?->etiqueta() ?? '—' }}</td>
            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.aspirantes.show', $a) }}">Ver detalle</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">No hay aspirantes con los filtros seleccionados.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $aspirantes->links() }}
@endsection
