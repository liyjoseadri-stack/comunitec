<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <link rel="icon" type="image/png" sizes="1254x1254" href="{{ asset('images/favicon-comunitec.png') }}">
        <title>Iniciar sesión | COMUN&amp;TEC</title>
        <link rel="stylesheet" href="{{ asset('css/administracion.css') }}">
    </head>
    <body class="acceso">
        <main class="acceso__contenedor">
            <section class="acceso__tarjeta" aria-labelledby="titulo-acceso">
                <img
                    class="acceso__logotipo"
                    src="{{ asset('images/logo-comunitec-transparente.png') }}"
                    alt="COMUN&TEC, comercialización e instalación de tecnologías"
                    width="1469"
                    height="917"
                >
                <div class="acceso__encabezado">
                    <h1 id="titulo-acceso">Bienvenido</h1>
                    <p>Ingresa para administrar cotizaciones, inventario y ventas.</p>
                </div>

                <form class="formulario-administrativo acceso__formulario" method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <x-campo-formulario id="correo" nombre="correo" etiqueta="Correo electrónico" tipo="email" :valor="old('correo')" autocomplete="email" required autofocus />
                    <x-campo-formulario id="contrasena" nombre="contrasena" etiqueta="Contraseña" tipo="password" autocomplete="current-password" required />
                    <label class="control-verificacion" for="recordar">
                        <input id="recordar" name="recordar" type="checkbox" value="1"
                            @if ($errors->has('recordar')) aria-invalid="true" aria-describedby="recordar-error" @endif
                        >
                        <span>Recordarme en este equipo</span>
                    </label>
                    @error('recordar')
                        <p id="recordar-error" class="error-campo">{{ $message }}</p>
                    @enderror
                    <button type="submit">Iniciar sesión</button>
                </form>
            </section>
        </main>
    </body>
</html>
