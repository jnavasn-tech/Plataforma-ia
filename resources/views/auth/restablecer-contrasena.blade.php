@extends('layouts.app')
@section('titulo', 'Restablecer contraseña')
@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Crear nueva contraseña</h1>
                <form method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input id="correo" name="correo" type="email" class="form-control @error('correo') is-invalid @enderror" value="{{ old('correo', $correo) }}" required autocomplete="email">
                        @error('correo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Nueva contraseña</label>
                        <input id="contrasena" name="contrasena" type="password" class="form-control @error('contrasena') is-invalid @enderror" required autocomplete="new-password" aria-describedby="ayuda-contrasena">
                        <div id="ayuda-contrasena" class="form-text">Mínimo 8 caracteres, con al menos una letra mayúscula, una minúscula y un número.</div>
                        @error('contrasena')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="contrasena_confirmation" class="form-label">Confirmar nueva contraseña</label>
                        <input id="contrasena_confirmation" name="contrasena_confirmation" type="password" class="form-control" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar contraseña</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
