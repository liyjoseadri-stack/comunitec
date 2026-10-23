<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <link rel="icon" type="image/png" sizes="1254x1254" href="{{ asset('images/favicon-comunitec.png') }}">
        <title>@yield('titulo', 'Administración') | COMUN&amp;TEC</title>
        <link rel="stylesheet" href="{{ asset('css/navegacion.css') }}?v={{ filemtime(public_path('css/navegacion.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/administracion.css') }}?v={{ filemtime(public_path('css/administracion.css')) }}">
        @stack('estilos')
    </head>
    <body class="aplicacion">
        <a class="saltar-contenido" href="#contenido-principal">Saltar al contenido</a>
        <div class="estructura-aplicacion">
            @include('componentes.navegacion-lateral')
            <div class="estructura-aplicacion__principal">
                @include('componentes.barra-superior')
                <main class="pagina-administrativa @yield('clase_pagina')" id="contenido-principal" tabindex="-1">
                    <x-alerta-flash />
                    @include('componentes.errores-validacion')
                    @yield('contenido')
                </main>
            </div>
        </div>
        <button class="fondo-menu" type="button" aria-label="Cerrar menú"></button>
        <x-dialogo-confirmacion />

        <script src="{{ asset('js/navegacion.js') }}?v={{ filemtime(public_path('js/navegacion.js')) }}" defer></script>
        @vite('resources/js/app.js')
        @stack('scripts')
    </body>
</html>
