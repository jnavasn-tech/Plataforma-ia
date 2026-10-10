@extends('layouts.app')
@section('titulo', 'Mi perfil')
@section('contenido')
<h1 class="h3 mb-4">Mi perfil</h1>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Datos personales</h2>
                <form method="POST" action="{{ route('perfil.update') }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre completo</label>
                        <input id="nombre" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $usuario->nombre) }}" required maxlength="120">
                        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input id="correo" class="form-control" value="{{ $usuario->correo }}" disabled aria-describedby="ayuda-correo">
                        <div id="ayuda-correo" class="form-text">El correo identifica su cuenta y no puede modificarse desde esta pantalla.</div>
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono (opcional)</label>
                        <input id="telefono" name="telefono" class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono', $usuario->telefono) }}" maxlength="20" inputmode="tel">
                        @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if($usuario->esAdministrador())
                        <div class="mb-3">
                            <label for="cargo" class="form-label">Cargo</label>
                            <input id="cargo" name="cargo" class="form-control" value="{{ old('cargo', $usuario->cargo) }}" maxlength="100">
                        </div>
                    @endif
                    <button class="btn btn-primary" type="submit">Guardar datos</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Cambiar contraseña</h2>
                @if($errors->contrasena->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">@foreach($errors->contrasena->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('perfil.contrasena') }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="contrasena_actual" class="form-label">Contraseña actual</label>
                        <input id="contrasena_actual" name="contrasena_actual" type="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Nueva contraseña</label>
                        <input id="contrasena" name="contrasena" type="password" class="form-control" required autocomplete="new-password" aria-describedby="ayuda-nueva">
                        <div id="ayuda-nueva" class="form-text">La contraseña debe tener mínimo 8 caracteres, al menos una letra mayúscula, una minúscula y un número.</div>
                    </div>
                    <div class="mb-3">
                        <label for="contrasena_confirmation" class="form-label">Confirmar nueva contraseña</label>
                        <input id="contrasena_confirmation" name="contrasena_confirmation" type="password" class="form-control" required autocomplete="new-password">
                    </div>
                    <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
                </form>
                <form method="POST" action="{{ route('perfil.enlace') }}" class="mt-3">
                    @csrf
                    <button class="btn btn-link p-0 small" type="submit">¿No recuerda su contraseña actual? Enviar enlace de restablecimiento a mi correo</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
