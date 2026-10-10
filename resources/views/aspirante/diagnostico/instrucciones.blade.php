@extends('layouts.app')
@section('titulo', 'Instrucciones del diagnóstico')
@section('contenido')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4">{{ $tipo->etiqueta() }}</h1>
                <p>El diagnóstico tiene <strong>{{ $areas->count() * $itemsPorArea }} preguntas</strong> de opción múltiple, {{ $itemsPorArea }} por cada área:</p>
                <ul>@foreach($areas as $area)<li>{{ $area->nombre }}</li>@endforeach</ul>
                <h2 class="h6">Antes de comenzar</h2>
                <ul class="small">
                    <li>Tiempo estimado: de 30 a 45 minutos. No hay límite de tiempo por pregunta.</li>
                    <li>La dificultad se ajusta según sus respuestas: si acierta, la siguiente pregunta del área es más exigente; si no, es más sencilla.</li>
                    <li>Debe seleccionar una respuesta para avanzar. No es posible regresar a preguntas anteriores.</li>
                    <li>Si su sesión vence, su avance queda guardado y podrá continuar desde la pregunta en que quedó.</li>
                    <li>Responda con honestidad: el resultado se usa para recomendarle una ruta de aprendizaje.</li>
                </ul>
                @if($motivo)
                    <div class="alert alert-info" role="status">{{ $motivo }}</div>
                    <a href="{{ route('aspirante.panel') }}" class="btn btn-outline-secondary">Volver al panel</a>
                @else
                    <form method="POST" action="{{ route('aspirante.diagnostico.iniciar', $tipo->value) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">{{ $activo ? 'Continuar diagnóstico' : 'Confirmar e iniciar' }}</button>
                        <a href="{{ route('aspirante.panel') }}" class="btn btn-link">Cancelar</a>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
