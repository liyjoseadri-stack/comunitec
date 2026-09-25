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
        @include('componentes.navegacion')

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

        @stack('scripts')
    </body>
</html>
