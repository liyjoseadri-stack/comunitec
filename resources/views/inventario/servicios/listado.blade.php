@extends('layouts.aplicacion')

@section('titulo', 'Servicios')

@section('contenido')
    @include('componentes.errores-validacion')
    @if (session('success'))
        <p class="mensaje-exito">{{ session('success') }}</p>
    @endif

    <section id="nuevo-servicio" class="bloque-administrativo">
        <h2>Registrar servicio</h2>
        <form class="formulario-administrativo formulario-catalogo" method="POST" action="{{ route('inventario.servicios.guardar') }}">
            @csrf
            @include('inventario.servicios.campos', ['servicio' => null])
            <x-boton tipo="submit">Guardar servicio</x-boton>
        </form>
    </section>

    <section class="bloque-administrativo">
        <h2>Servicios registrados</h2>
        <form class="formulario-filtros" method="GET">
            <div class="campo-formulario">
                <label for="buscar">Buscar</label>
                <input id="buscar" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o código">
            </div>
            <div class="campo-formulario">
                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria">
                    <option value="">Todas</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected(request('categoria') == $categoria->id)>{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="campo-formulario">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="activos" @selected(request('estado') === 'activos')>Activos</option>
                    <option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option>
                </select>
            </div>
            <x-boton tipo="submit" variante="secundario">Filtrar</x-boton>
        </form>
        <div class="contenedor-tabla" tabindex="0">
            <table class="tabla-registros">
                <thead><tr><th>Código</th><th>Servicio</th><th>Categoría</th><th>Unidad</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse ($servicios as $servicio)
                        <tr>
                            <td>{{ $servicio->codigo }}</td>
                            <td><strong>{{ $servicio->nombre }}</strong></td>
                            <td>{{ $servicio->categoria?->nombre }}</td>
                            <td>{{ $servicio->unidad }}</td>
                            <td>${{ number_format((float) $servicio->precio, 2) }}</td>
                            <td><x-insignia-estado :estado="$servicio->activo ? 'activo' : 'inactivo'" /></td>
                            <td class="acciones-tabla">
                                <x-boton compacto variante="contorno" :href="route('inventario.servicios.detalle', $servicio)">Ver/Editar</x-boton>
                                <form method="POST" action="{{ route('inventario.servicios.estado', $servicio) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-boton compacto tipo="submit" :variante="$servicio->activo ? 'peligro' : 'exito'">{{ $servicio->activo ? 'Desactivar' : 'Activar' }}</x-boton>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="estado-vacio">No hay servicios con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('componentes.paginacion', ['paginador' => $servicios])
    </section>
@endsection
