<header class="barra-superior">
    <button
        id="boton-menu"
        class="barra-superior__menu"
        type="button"
        aria-label="Abrir menú de navegación"
        aria-controls="navegacion-lateral"
        aria-expanded="false"
    >
        <x-icono nombre="menu" />
    </button>

    <div class="barra-superior__contexto">
        <span>COMUN&amp;TEC</span>
        <h1 class="barra-superior__titulo">@yield('titulo', 'Administración')</h1>
    </div>

    <div class="barra-superior__usuario">
        <a href="{{ route('perfil.mostrar') }}">
            <span class="barra-superior__avatar" aria-hidden="true">
                {{ mb_strtoupper(mb_substr(auth()->user()->nombre, 0, 1)) }}
            </span>
            <span class="barra-superior__identidad">
                <strong>{{ auth()->user()->nombre }}</strong>
                <small>{{ ucfirst(auth()->user()->rol) }}</small>
            </span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Cerrar sesión" aria-label="Cerrar sesión">
                <x-icono nombre="cerrar" />
            </button>
        </form>
    </div>
</header>
