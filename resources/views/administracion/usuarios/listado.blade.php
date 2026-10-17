@extends('layouts.aplicacion')

@section('titulo', 'Usuarios')

@section('contenido')
    <x-encabezado-pagina titulo="Usuarios">
        <x-slot:acciones>
            <span class="contador-registros">{{ $usuarios->total() }} {{ $usuarios->total() === 1 ? 'usuario' : 'usuarios' }}</span>
            <x-boton :href="route('administracion.usuarios.crear')">Crear usuario</x-boton>
        </x-slot:acciones>
    </x-encabezado-pagina>

    <section class="bloque-administrativo" aria-labelledby="titulo-usuarios-registrados">
        <h2 id="titulo-usuarios-registrados">Usuarios registrados</h2>

        <form class="formulario-filtros" method="GET" action="{{ route('administracion.usuarios.listado') }}">
            <x-campo-formulario id="buscar-usuarios" nombre="buscar" etiqueta="Buscar usuarios" :valor="request('buscar')" placeholder="Nombre o correo electrónico" />
            <x-campo-formulario id="rol-usuarios" nombre="rol" etiqueta="Rol" tipo="select">
                <option value="">Todos</option>
                <option value="administrador" @selected(request('rol') === 'administrador')>Administrador</option>
                <option value="comercial" @selected(request('rol') === 'comercial')>Comercial</option>
                <option value="consulta" @selected(request('rol') === 'consulta')>Consulta</option>
            </x-campo-formulario>
            <x-campo-formulario id="estado-usuarios" nombre="estado" etiqueta="Estado" tipo="select">
                <option value="">Todos</option>
                <option value="activo" @selected(request('estado') === 'activo')>Activo</option>
                <option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivo</option>
            </x-campo-formulario>
            <x-grupo-acciones-fila>
                <x-boton tipo="submit" variante="secundario">Aplicar filtros</x-boton>
                @if (request()->filled('buscar') || request()->filled('rol') || request()->filled('estado'))
                    <x-boton variante="contorno" :href="route('administracion.usuarios.listado')">Limpiar</x-boton>
                @endif
            </x-grupo-acciones-fila>
        </form>

        <x-tabla-responsive etiqueta="Usuarios registrados" adaptable :vacia="$usuarios->isEmpty()">
            <table class="tabla-registros tabla-adaptable tabla-catalogo">
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
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <th scope="row" data-etiqueta="Nombre"><strong>{{ $usuario->nombre }}</strong></th>
                            <td data-etiqueta="Correo">{{ $usuario->correo }}</td>
                            <td data-etiqueta="Rol">{{ ucfirst($usuario->rol) }}</td>
                            <td data-etiqueta="Estado"><x-insignia-estado :estado="$usuario->activo ? 'activo' : 'inactivo'" /></td>
                            <td data-etiqueta="Acciones">
                                <x-grupo-acciones-fila>
                                    @if ($usuario->is(auth()->user()))
                                        <x-boton compacto variante="contorno" :href="route('perfil.mostrar')">Mi perfil</x-boton>
                                    @else
                                        <x-boton compacto variante="contorno" :href="route('administracion.usuarios.editar', $usuario)">Editar</x-boton>
                                        <form method="POST" action="{{ route('administracion.usuarios.estado', $usuario) }}"
                                            @if ($usuario->activo)
                                                data-confirmar data-confirmar-titulo="Desactivar usuario"
                                                data-confirmar-mensaje="{{ $usuario->nombre }} dejará de poder acceder al sistema. Su sesión se cerrará en su próxima solicitud."
                                                data-confirmar-etiqueta="Desactivar"
                                            @endif
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <input name="activo" type="hidden" value="{{ $usuario->activo ? 0 : 1 }}">
                                            <x-boton :variante="$usuario->activo ? 'peligro' : 'exito'" tipo="submit" compacto>
                                                {{ $usuario->activo ? 'Desactivar' : 'Activar' }}
                                            </x-boton>
                                        </form>
                                    @endif
                                </x-grupo-acciones-fila>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="tabla-estado-vacio">
                                <x-estado-vacio :mensaje="request()->hasAny(['buscar', 'rol', 'estado']) ? 'No hay usuarios con estos filtros.' : 'Aún no hay usuarios registrados.'">
                                    <x-slot:acciones>
                                        @if (request()->hasAny(['buscar', 'rol', 'estado']))
                                            <x-boton variante="contorno" :href="route('administracion.usuarios.listado')">Limpiar filtros</x-boton>
                                        @else
                                            <x-boton variante="contorno" :href="route('administracion.usuarios.crear')">Crear usuario</x-boton>
                                        @endif
                                    </x-slot:acciones>
                                </x-estado-vacio>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-tabla-responsive>

        @include('componentes.paginacion', ['paginador' => $usuarios])
    </section>
@endsection
