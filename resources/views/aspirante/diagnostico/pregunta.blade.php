@extends('layouts.app')
@section('titulo', 'Pregunta '.($progreso['respondidas'] + 1))
@section('contenido')
<div class="row justify-content-center">
    <div class="col-lg-8">
        {{-- Pantalla 8: progreso del diagnóstico --}}
        <div class="mb-3" aria-live="polite">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Área {{ $progreso['indice_area'] }} de {{ $progreso['total_areas'] }}: <strong>{{ $progreso['area_actual'] }}</strong></span>
                <span>Pregunta {{ $progreso['respondidas'] + 1 }} de {{ $progreso['total'] }}</span>
            </div>
            <div class="progress" role="progressbar" aria-label="Avance del diagnóstico" aria-valuenow="{{ $progreso['porcentaje'] }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: {{ $progreso['porcentaje'] }}%"></div>
            </div>
        </div>
        {{-- Pantalla 7: pregunta del diagnóstico --}}
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('aspirante.diagnostico.responder', $diagnostico) }}">
                    @csrf
                    <input type="hidden" name="id_respuesta" value="{{ $item->id_respuesta }}">
                    <fieldset>
                        <legend class="h5 mb-3">{{ $item->pregunta->enunciado }}</legend>
                        @foreach($item->pregunta->opciones as $opcion)
                            <div class="form-check border rounded p-3 ps-5 mb-2 bg-white">
                                <input class="form-check-input" type="radio" name="id_opcion" id="opcion-{{ $opcion->id_opcion }}" value="{{ $opcion->id_opcion }}" required>
                                <label class="form-check-label w-100" for="opcion-{{ $opcion->id_opcion }}">{{ $opcion->texto }}</label>
                            </div>
                        @endforeach
                    </fieldset>
                    @error('id_opcion')<div class="text-danger small mb-2" role="alert">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-primary mt-2">Responder y continuar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
