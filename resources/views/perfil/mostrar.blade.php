@extends('layouts.aplicacion')

@section('titulo', 'Mi perfil')

@section('contenido')
    <div class="cuadricula-perfil">
        <section class="bloque-administrativo" aria-labelledby="titulo-datos-perfil">
            <div class="encabezado-seccion encabezado-seccion--acciones">
                <h2 id="titulo-datos-perfil">Datos personales</h2>
                <x-insignia-estado :estado="$usuario->activo ? 'activo' : 'inactivo'" />
            </div>
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
                <x-campo-formulario id="nombre-perfil" nombre="nombre" etiqueta="Nombre" :valor="old('nombre', $usuario->nombre)" required
                        maxlength="255"
                        autocomplete="name" />
                <x-campo-formulario id="correo-perfil" nombre="correo" etiqueta="Correo electrónico" tipo="email" :valor="old('correo', $usuario->correo)" required
                        maxlength="255"
                        autocomplete="email" />
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
                <x-campo-formulario id="contrasena-actual" nombre="contrasena_actual" etiqueta="Contraseña actual" tipo="password" required
                        autocomplete="current-password" />
                <x-campo-formulario id="contrasena-nueva" nombre="contrasena" etiqueta="Nueva contraseña" tipo="password" required
                        minlength="8"
                        autocomplete="new-password" />
                <x-campo-formulario id="confirmar-contrasena" nombre="contrasena_confirmation" etiqueta="Confirmar nueva contraseña" tipo="password" required
                        minlength="8"
                        autocomplete="new-password" />
                <div class="acciones-formulario">
                    <x-boton tipo="submit">Actualizar contraseña</x-boton>
                </div>
            </form>
        </section>
    </div>
@endsection
