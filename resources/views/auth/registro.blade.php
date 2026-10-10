@extends('layouts.app')
@section('titulo', 'Crear cuenta')
@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Crear cuenta</h1>
                <form method="POST" action="{{ route('registro.store') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre completo</label>
                        <input id="nombre" name="nombre" type="text" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required maxlength="120" autocomplete="name" @error('nombre') aria-describedby="error-nombre" @enderror>
                        @error('nombre')<div id="error-nombre" class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input id="correo" name="correo" type="email" class="form-control @error('correo') is-invalid @enderror" value="{{ old('correo') }}" required maxlength="150" autocomplete="email">
                        @error('correo')
                            <div class="invalid-feedback">{{ $message }} @if(str_contains($message, 'registrado'))<a href="{{ route('login') }}">Iniciar sesión</a> · <a href="{{ route('password.request') }}">Recuperar contraseña</a>@endif</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <input id="contrasena" name="contrasena" type="password" class="form-control @error('contrasena') is-invalid @enderror" required autocomplete="new-password" aria-describedby="ayuda-contrasena">
                        <div id="ayuda-contrasena" class="form-text">Mínimo 8 caracteres, con al menos una letra mayúscula, una minúscula y un número.</div>
                        @error('contrasena')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="contrasena_confirmation" class="form-label">Confirmar contraseña</label>
                        <input id="contrasena_confirmation" name="contrasena_confirmation" type="password" class="form-control" required autocomplete="new-password">
                    </div>
                    <fieldset class="mb-3">
                        <legend class="fs-6 fw-semibold">Declaraciones</legend>
                        <div class="form-check">
                            <input class="form-check-input @error('declara_elegibilidad') is-invalid @enderror" type="checkbox" value="1" id="declara_elegibilidad" name="declara_elegibilidad" @checked(old('declara_elegibilidad'))>
                            <label class="form-check-label" for="declara_elegibilidad">Declaro que tengo entre {{ config('plataforma.privacidad.edad_minima') }} y {{ config('plataforma.privacidad.edad_maxima') }} años y que egresé del nivel diversificado.</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="es_menor_edad" name="es_menor_edad" @checked(old('es_menor_edad'))>
                            <label class="form-check-label" for="es_menor_edad">Soy menor de 18 años.</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input @error('consentimiento_tutor') is-invalid @enderror" type="checkbox" value="1" id="consentimiento_tutor" name="consentimiento_tutor" @checked(old('consentimiento_tutor'))>
                            <label class="form-check-label" for="consentimiento_tutor">Si soy menor de edad, cuento con el consentimiento de mi madre, padre o tutor.</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input @error('acepta_privacidad') is-invalid @enderror" type="checkbox" value="1" id="acepta_privacidad" name="acepta_privacidad" @checked(old('acepta_privacidad'))>
                            <label class="form-check-label" for="acepta_privacidad">He leído y acepto el <a href="{{ route('privacidad') }}" target="_blank" rel="noopener">aviso de privacidad y tratamiento de datos</a>.</label>
                        </div>
                    </fieldset>
                    <button type="submit" class="btn btn-primary w-100">Crear cuenta</button>
                </form>
                <p class="mt-3 mb-0 text-center small">¿Ya tiene cuenta? <a href="{{ route('login') }}">Inicie sesión</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
