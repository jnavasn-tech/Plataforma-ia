@if(session('estado'))
    <div class="alert alert-success" role="status">{{ session('estado') }}</div>
@endif
@if(session('aviso'))
    <div class="alert alert-warning" role="alert">{{ session('aviso') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <p class="fw-semibold mb-1">Revise la información ingresada:</p>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
