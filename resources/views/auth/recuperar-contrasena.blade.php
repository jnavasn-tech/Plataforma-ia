@extends('layouts.app')
@section('titulo', 'Recuperar contraseña')
@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-2">Recuperar contraseña</h1>
                <p class="text-muted small">Ingrese su correo. Si está registrado, recibirá un enlace para crear una nueva contraseña.</p>
                <form method="POST" action="{{ route('password.email') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input id="correo" name="correo" type="email" class="form-control @error('correo') is-invalid @enderror" value="{{ old('correo') }}" required autocomplete="email">
                        @error('correo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Enviar enlace</button>
                </form>
                <p class="mt-3 mb-0 small text-center"><a href="{{ route('login') }}">Volver a iniciar sesión</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
