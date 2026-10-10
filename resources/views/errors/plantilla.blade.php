@extends('layouts.app')
@section('titulo', $titulo)
@section('contenido')
<div class="text-center py-5">
    <p class="display-5 fw-bold text-primary mb-2">{{ $codigo }}</p>
    <h1 class="h3">{{ $titulo }}</h1>
    <p class="text-muted">{{ $mensaje }}</p>
    <a href="{{ auth()->check() ? route('panel') : route('inicio') }}" class="btn btn-primary">Volver al inicio</a>
</div>
@endsection
