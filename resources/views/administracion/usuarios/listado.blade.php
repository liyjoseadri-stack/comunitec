@extends('layouts.aplicacion')

@section('titulo', 'Usuarios')

@section('contenido')
    <x-encabezado-pagina
        titulo="Administración de usuarios"
        descripcion="Gestiona el acceso y los permisos del personal del sistema."
    >
        <x-slot:acciones>
            <span class="contador-registros">{{ $usuarios->count() }} usuarios</span>
        </x-slot:acciones>
    </x-encabezado-pagina>

    @include('componentes.errores-validacion')

    <section class="bloque-administrativo" aria-labelledby="titulo-crear-usuario">
        <h2 id="titulo-crear-usuario">Crear usuario</h2>
        <form class="formulario-administrativo formulario-dos-columnas" method="POST" action="{{ route('administracion.usuarios.guardar') }}">
            @csrf
            <div class="campo-formulario">
                <label for="nombre-usuario">Nombre</label>
                <input id="nombre-usuario" name="nombre" value="{{ old('nombre') }}" required>
            </div>
            <div class="campo-formulario">
                <label for="correo-usuario">Correo electrónico</label>
                <input id="correo-usuario" name="correo" type="email" value="{{ old('correo') }}" required>
            </div>
            <div class="campo-formulario">
                <label for="rol-usuario">Rol</label>
                <select id="rol-usuario" name="rol" required>
                    <option value="comercial">Comercial</option>
                    <option value="consulta">Consulta</option>
                    <option value="administrador">Administrador</option>
                </select>
            </div>
            <div class="campo-formulario">
                <label for="contrasena-usuario">Contraseña</label>
                <input id="contrasena-usuario" name="contrasena" type="password" required>
            </div>
            <div class="campo-formulario">
                <label for="confirmar-contrasena-usuario">Confirmar contraseña</label>
                <input id="confirmar-contrasena-usuario" name="contrasena_confirmation" type="password" required>
            </div>
            <div class="acciones-formulario campo-formulario--ancho">
                <x-boton tipo="submit">Crear usuario</x-boton>
            </div>
        </form>
    </section>

    <section class="bloque-administrativo" aria-labelledby="titulo-usuarios-registrados">
        <h2 id="titulo-usuarios-registrados">Usuarios registrados</h2>
        <div class="contenedor-tabla" tabindex="0" aria-label="Usuarios registrados">
            <table class="tabla-registros tabla-catalogo">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td><strong>{{ $usuario->nombre }}</strong></td>
                            <td>{{ $usuario->correo }}</td>
                            <td>{{ ucfirst($usuario->rol) }}</td>
                            <td>
                                <x-insignia-estado :estado="$usuario->activo ? 'activo' : 'inactivo'" />
                            </td>
                            <td>
                                <div class="grupo-acciones">
                                    <form method="POST" action="{{ route('administracion.usuarios.estado', $usuario) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input name="activo" type="hidden" value="{{ $usuario->activo ? 0 : 1 }}">
                                        <x-boton :variante="$usuario->activo ? 'peligro' : 'exito'" tipo="submit" compacto>
                                            {{ $usuario->activo ? 'Desactivar' : 'Activar' }}
                                        </x-boton>
                                    </form>
                                    <details class="editor-tabla">
                                        <summary class="boton boton--secundario boton--compacto">Editar</summary>
                                        <form class="formulario-administrativo editor-tabla__formulario" method="POST" action="{{ route('administracion.usuarios.actualizar', $usuario) }}">
                                            @csrf
                                            @method('PUT')
                                            <label>Nombre <input name="nombre" value="{{ $usuario->nombre }}" required></label>
                                            <label>Correo <input name="correo" type="email" value="{{ $usuario->correo }}" required></label>
                                            <label>Rol
                                                <select name="rol">
                                                    @foreach (['administrador' => 'Administrador', 'comercial' => 'Comercial', 'consulta' => 'Consulta'] as $valor => $etiqueta)
                                                        <option value="{{ $valor }}" @selected($usuario->rol === $valor)>{{ $etiqueta }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label>Nueva contraseña <input name="contrasena" type="password"></label>
                                            <label>Confirmar contraseña <input name="contrasena_confirmation" type="password"></label>
                                            <x-boton tipo="submit">Guardar cambios</x-boton>
                                        </form>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
