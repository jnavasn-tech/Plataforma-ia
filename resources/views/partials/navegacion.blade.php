<nav class="navbar navbar-expand-lg navbar-dark bg-primary" aria-label="Navegación principal">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ auth()->check() ? route('panel') : route('inicio') }}">Competencias IA</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Mostrar u ocultar el menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                @auth
                    @if(auth()->user()->esAdministrador())
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.panel')) active @endif" href="{{ route('admin.panel') }}">Panel</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.aspirantes.*')) active @endif" href="{{ route('admin.aspirantes.index') }}">Aspirantes</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Contenido</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('admin.modulos.index') }}">Módulos</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.recursos.index') }}">Catálogo de recursos</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.preguntas.index') }}">Banco de preguntas</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.procesos.index') }}">Procesos de selección</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.reportes.*')) active @endif" href="{{ route('admin.reportes.index') }}">Reportes</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.prompts.*')) active @endif" href="{{ route('admin.prompts.index') }}">Prompts IA</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.bitacora.*')) active @endif" href="{{ route('admin.bitacora.index') }}">Bitácora</a></li>
                    @else
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('aspirante.panel')) active @endif" href="{{ route('aspirante.panel') }}">Mi panel</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('aspirante.ruta', 'aspirante.modulo', 'aspirante.recurso')) active @endif" href="{{ route('aspirante.ruta') }}">Mi ruta</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('aspirante.progreso')) active @endif" href="{{ route('aspirante.progreso') }}">Mi progreso</a></li>
                    @endif
                @endauth
            </ul>
            <ul class="navbar-nav">
                @auth
                    <li class="nav-item"><a class="nav-link" href="{{ route('perfil.edit') }}">{{ auth()->user()->nombre }}</a></li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link">Cerrar sesión</button>
                        </form>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Iniciar sesión</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('registro') }}">Crear cuenta</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
