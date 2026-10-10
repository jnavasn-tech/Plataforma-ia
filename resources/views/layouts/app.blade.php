<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(!empty($refrescar))
        {{-- Mientras la ruta se genera en segundo plano, la página se actualiza cada 5 segundos sin JavaScript. --}}
        <meta http-equiv="refresh" content="5">
    @endif
    <title>@yield('titulo', 'Inicio') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <a class="visually-hidden-focusable btn btn-primary position-absolute m-2" href="#contenido">Saltar al contenido principal</a>
    @include('partials.navegacion')
    <main id="contenido" class="container py-4 flex-grow-1" tabindex="-1">
        @include('partials.alertas')
        @yield('contenido')
    </main>
    @include('partials.pie')
</body>
</html>
