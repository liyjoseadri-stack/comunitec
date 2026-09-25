<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <title>@yield('titulo', 'Administración') | COMUN&amp;TEC</title>
        <link rel="stylesheet" href="{{ asset('css/navegacion.css') }}">
        <link rel="stylesheet" href="{{ asset('css/administracion.css') }}">
        @stack('estilos')
    </head>
    <body class="aplicacion">
        <div class="estructura-aplicacion">
            @include('componentes.navegacion-lateral')
            <div class="estructura-aplicacion__principal">
                @include('componentes.barra-superior')
                <main class="pagina-administrativa @yield('clase_pagina')" id="contenido-principal">
                    @if (session('estado'))
                        <div class="alerta alerta--exito" role="status">
                            {{ session('estado') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alerta alerta--peligro" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    @yield('contenido')
                </main>
            </div>
        </div>
        <button class="fondo-menu" type="button" aria-label="Cerrar menú"></button>

        <script src="{{ asset('js/navegacion.js') }}" defer></script>
        @stack('scripts')
    </body>
</html>
