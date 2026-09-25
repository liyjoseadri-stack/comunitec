<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
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
                    <div class="campo-formulario">
                        <label for="correo">Correo electrónico</label>
                        <input id="correo" name="correo" type="email" value="{{ old('correo') }}" autocomplete="email" required autofocus>
                        @error('correo')
                            <p class="error-campo" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="campo-formulario">
                        <label for="contrasena">Contraseña</label>
                        <input id="contrasena" name="contrasena" type="password" autocomplete="current-password" required>
                    </div>
                    <label class="control-verificacion" for="recordar">
                        <input id="recordar" name="recordar" type="checkbox" value="1">
                        <span>Recordarme en este equipo</span>
                    </label>
                    <button type="submit">Iniciar sesión</button>
                </form>
            </section>
        </main>
    </body>
</html>
