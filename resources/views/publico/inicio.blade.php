@extends('layouts.app')
@section('titulo', 'Inicio')
@section('contenido')
<section class="p-4 p-md-5 mb-4 bg-white rounded-3 shadow-sm">
    <h1 class="display-6 fw-bold">Diagnostique y fortalezca sus competencias informáticas</h1>
    <p class="lead col-lg-9">Plataforma gratuita para jóvenes egresados del nivel diversificado que aspiran a puestos de trabajo en Grupo Ilimitado, S. A. Realice un diagnóstico de 30 preguntas, conozca su perfil y siga una ruta de aprendizaje personalizada.</p>
    <div class="d-flex flex-wrap gap-2">
        @auth
            <a href="{{ route('panel') }}" class="btn btn-primary btn-lg">Ir a mi panel</a>
        @else
            <a href="{{ route('registro') }}" class="btn btn-primary btn-lg">Crear cuenta</a>
            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-lg">Iniciar sesión</a>
        @endauth
    </div>
</section>
<section aria-labelledby="titulo-areas">
    <h2 id="titulo-areas" class="h4 mb-3">Áreas que evalúa la plataforma</h2>
    <div class="row row-cols-1 row-cols-md-3 g-3">
        @foreach($areas as $area)
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="h6 card-title">{{ $area->nombre }}</h3>
                        <p class="card-text small text-muted mb-0">{{ $area->descripcion }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
<section class="mt-4" aria-labelledby="titulo-pasos">
    <h2 id="titulo-pasos" class="h4">¿Cómo funciona?</h2>
    <ol class="list-group list-group-numbered">
        <li class="list-group-item">Cree su cuenta y confirme su correo electrónico.</li>
        <li class="list-group-item">Responda el diagnóstico de competencias (30 preguntas, de 30 a 45 minutos).</li>
        <li class="list-group-item">Consulte su perfil y la ruta de aprendizaje recomendada.</li>
        <li class="list-group-item">Complete los módulos, sus evaluaciones y el diagnóstico final.</li>
    </ol>
</section>
@endsection
