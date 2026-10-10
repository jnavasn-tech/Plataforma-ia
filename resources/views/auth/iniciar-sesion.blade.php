@extends('layouts.app')
@section('titulo', 'Iniciar sesión')
@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Iniciar sesión</h1>
                <form method="POST" action="{{ route('login.store') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input id="correo" name="correo" type="email" class="form-control @error('correo') is-invalid @enderror" value="{{ old('correo') }}" required autocomplete="email" autofocus>
                        @error('correo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <input id="contrasena" name="contrasena" type="password" class="form-control @error('contrasena') is-invalid @enderror" required autocomplete="current-password">
                        @error('contrasena')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="recordar" name="recordar">
                        <label class="form-check-label" for="recordar">Mantener la sesión en este dispositivo</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                </form>
                <div class="d-flex justify-content-between mt-3 small">
                    <a href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>
                    <a href="{{ route('registro') }}">Crear cuenta</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
