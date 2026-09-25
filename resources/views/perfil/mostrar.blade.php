@extends('layouts.aplicacion')

@section('titulo', 'Mi perfil')

@section('contenido')
    <x-encabezado-pagina
        titulo="Mi perfil"
        descripcion="Actualiza tus datos personales y la seguridad de tu cuenta."
    >
        <x-slot:acciones>
            <x-insignia-estado :estado="$usuario->activo ? 'activo' : 'inactivo'" />
        </x-slot:acciones>
    </x-encabezado-pagina>

    @include('componentes.errores-validacion')

    <div class="cuadricula-perfil">
        <section class="bloque-administrativo" aria-labelledby="titulo-datos-perfil">
            <h2 id="titulo-datos-perfil">Datos personales</h2>
            <p class="ayuda-campo">
                Tu rol es {{ ucfirst($usuario->rol) }} y solo un administrador puede cambiarlo.
            </p>
            <form
                class="formulario-administrativo formulario-enfocado"
                method="POST"
                action="{{ route('perfil.actualizar') }}"
            >
                @csrf
                @method('PUT')
                <div class="campo-formulario">
                    <label for="nombre-perfil">Nombre</label>
                    <input
                        id="nombre-perfil"
                        name="nombre"
                        value="{{ old('nombre', $usuario->nombre) }}"
                        required
                        maxlength="255"
                        autocomplete="name"
                    >
                </div>
                <div class="campo-formulario">
                    <label for="correo-perfil">Correo electrónico</label>
                    <input
                        id="correo-perfil"
                        name="correo"
                        type="email"
                        value="{{ old('correo', $usuario->correo) }}"
                        required
                        maxlength="255"
                        autocomplete="email"
                    >
                </div>
                <div class="acciones-formulario">
                    <x-boton tipo="submit">Guardar perfil</x-boton>
                </div>
            </form>
        </section>

        <section class="bloque-administrativo" aria-labelledby="titulo-contrasena-perfil">
            <h2 id="titulo-contrasena-perfil">Cambiar contraseña</h2>
            <form
                class="formulario-administrativo formulario-enfocado"
                method="POST"
                action="{{ route('perfil.contrasena') }}"
            >
                @csrf
                @method('PUT')
                <div class="campo-formulario">
                    <label for="contrasena-actual">Contraseña actual</label>
                    <input
                        id="contrasena-actual"
                        name="contrasena_actual"
                        type="password"
                        required
                        autocomplete="current-password"
                    >
                </div>
                <div class="campo-formulario">
                    <label for="contrasena-nueva">Nueva contraseña</label>
                    <input
                        id="contrasena-nueva"
                        name="contrasena"
                        type="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >
                </div>
                <div class="campo-formulario">
                    <label for="confirmar-contrasena">Confirmar nueva contraseña</label>
                    <input
                        id="confirmar-contrasena"
                        name="contrasena_confirmation"
                        type="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >
                </div>
                <div class="acciones-formulario">
                    <x-boton tipo="submit">Actualizar contraseña</x-boton>
                </div>
            </form>
        </section>
    </div>
@endsection
