@extends('layouts.app')
@section('titulo', 'Confirme su correo')
@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4">Confirme su correo electrónico</h1>
                <p>Le enviamos un enlace de confirmación a <strong>{{ auth()->user()->correo }}</strong>. Debe confirmarlo para acceder al diagnóstico.</p>
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">Reenviar enlace</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
