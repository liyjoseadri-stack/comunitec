@extends('layouts.aplicacion')

@section('titulo', 'Categorías de inventario')

@section('contenido')
    <x-encabezado-pagina titulo="Categorías" descripcion="Organiza los productos y servicios del inventario.">
        <x-slot:acciones>
            <x-boton variante="contorno" :href="route('inventario.listado')">Volver a Inventario</x-boton>
        </x-slot:acciones>
    </x-encabezado-pagina>

    @include('componentes.errores-validacion')
    @if (session('success'))
        <p class="mensaje-exito" role="status">{{ session('success') }}</p>
    @endif

    <section class="bloque-administrativo" aria-labelledby="titulo-nueva-categoria">
        <h2 id="titulo-nueva-categoria">Nueva categoría</h2>
        <form class="formulario-administrativo formulario-filtros" method="POST" action="{{ route('inventario.categorias.guardar') }}">
            @csrf
            <div class="campo-formulario">
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="255">
            </div>
            <x-boton tipo="submit">Agregar categoría</x-boton>
        </form>
    </section>

    <section class="bloque-administrativo" aria-labelledby="titulo-listado-categorias">
        <h2 id="titulo-listado-categorias">Categorías registradas</h2>
        <form class="formulario-filtros" method="GET">
            <div class="campo-formulario">
                <label for="buscar">Buscar</label>
                <input id="buscar" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre de categoría">
            </div>
            <div class="campo-formulario">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="activas" @selected(request('estado') === 'activas')>Activas</option>
                    <option value="inactivas" @selected(request('estado') === 'inactivas')>Inactivas</option>
                </select>
            </div>
            <x-boton tipo="submit" variante="secundario">Filtrar</x-boton>
        </form>
        <div class="contenedor-tabla" tabindex="0">
            <table class="tabla-registros">
                <thead><tr><th>Nombre</th><th>Artículos</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse ($categorias as $categoria)
                        <tr>
                            <td><strong>{{ $categoria->nombre }}</strong></td>
                            <td>{{ $categoria->articulos_count }}</td>
                            <td><x-insignia-estado :estado="$categoria->activo ? 'activa' : 'inactiva'" /></td>
                            <td class="acciones-tabla">
                                <x-boton variante="contorno" :href="route('inventario.categorias.detalle', $categoria)" compacto>Ver</x-boton>
                                <form method="POST" action="{{ route('inventario.categorias.estado', $categoria) }}">
                                    @csrf @method('PATCH')
                                    <x-boton tipo="submit" :variante="$categoria->activo ? 'peligro' : 'exito'" compacto>
                                        {{ $categoria->activo ? 'Desactivar' : 'Activar' }}
                                    </x-boton>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="estado-vacio">No hay categorías con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('componentes.paginacion', ['paginador' => $categorias])
    </section>
@endsection
