@extends('layouts.app')
@section('titulo', 'Configuración de prompts')
@section('contenido')
<h1 class="h3">Configuración de prompts institucionales</h1>
<p class="small">Modelo configurado: <strong>{{ $modelo }}</strong> · Estado de la integración: <span class="badge text-bg-{{ $iaHabilitada ? 'success' : 'secondary' }}">{{ $iaHabilitada ? 'Habilitada' : 'Deshabilitada (se usan reglas)' }}</span></p>
<p class="small text-muted">Cada cambio crea una nueva versión. La estructura de salida (esquema JSON) y las validaciones de seguridad se aplican en el código y no dependen del texto del prompt.</p>
@foreach($claves as $clave)
    <div class="card shadow-sm mb-3"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">{{ $clave->etiqueta() }}</h2><a class="btn btn-sm btn-primary" href="{{ route('admin.prompts.create', ['clave' => $clave->value]) }}">Nueva versión</a></div>
        <table class="table table-sm mt-2 mb-0">
            <thead><tr><th scope="col">Versión</th><th scope="col">Descripción del cambio</th><th scope="col">Creado por</th><th scope="col">Fecha</th><th scope="col">Estado</th><th scope="col"></th></tr></thead>
            <tbody>@forelse($grupos->get($clave->value, collect()) as $p)
                <tr><td>v{{ $p->version }}</td><td>{{ $p->descripcion_cambio }}</td><td>{{ $p->creador?->nombre ?? 'Sistema' }}</td><td>{{ $p->created_at?->format('d/m/Y') }}</td>
                    <td>@if($p->activo)<span class="badge text-bg-success">Activa</span>@else<span class="text-muted">Inactiva</span>@endif</td>
                    <td>@unless($p->activo)<form method="POST" action="{{ route('admin.prompts.activar', $p) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary" type="submit">Activar</button></form>@endunless</td></tr>
                <tr><td colspan="6"><details><summary class="small">Ver contenido</summary><pre class="small bg-light p-2 mb-0 text-wrap">{{ $p->contenido }}</pre></details></td></tr>
            @empty
                <tr><td colspan="6" class="text-muted small">Sin versiones registradas; se usa el prompt de respaldo del repositorio.</td></tr>
            @endforelse</tbody>
        </table>
    </div></div>
@endforeach
@endsection
