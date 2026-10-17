@extends('layouts.aplicacion')

@section('titulo', 'Categorías de inventario')

@section('contenido')
    <div class="grupo-acciones acciones-pagina">
        <span class="contador-registros">{{ $categorias->total() }} {{ $categorias->total() === 1 ? 'categoría' : 'categorías' }}</span>
        <x-boton :href="route('inventario.categorias.crear')">Registrar categoría</x-boton>
    </div>

    <section class="bloque-administrativo" aria-labelledby="titulo-listado-categorias">
        <h2 id="titulo-listado-categorias">Categorías registradas</h2>
        <form class="formulario-filtros" method="GET">
            <x-campo-formulario id="buscar" nombre="buscar" etiqueta="Buscar" :valor="request('buscar')" placeholder="Nombre de categoría" />
            <x-campo-formulario id="estado" nombre="estado" etiqueta="Estado" tipo="select">
                    <option value="">Todos</option>
                    <option value="activas" @selected(request('estado') === 'activas')>Activas</option>
                    <option value="inactivas" @selected(request('estado') === 'inactivas')>Inactivas</option>

            </x-campo-formulario>
            <x-boton tipo="submit" variante="secundario">Filtrar</x-boton>
            @if (request()->filled('buscar') || request()->filled('estado'))
                <x-boton variante="contorno" :href="route('inventario.categorias.listado')">Limpiar</x-boton>
            @endif
        </form>
        <x-tabla-responsive etiqueta="Categorías registradas" adaptable>
            <table class="tabla-registros tabla-adaptable">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Artículos</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categorias as $categoria)
                        <tr>
                            <th scope="row" data-etiqueta="Nombre"><strong>{{ $categoria->nombre }}</strong></th>
                            <td data-etiqueta="Artículos">{{ $categoria->productos_count + $categoria->servicios_count }}</td>
                            <td data-etiqueta="Estado"><x-insignia-estado :estado="$categoria->activo ? 'activa' : 'inactiva'" /></td>
                            <td data-etiqueta="Acciones" class="acciones-tabla">
                                <x-grupo-acciones-fila>
                                    <x-boton variante="contorno" :href="route('inventario.categorias.detalle', $categoria)" compacto>Ver</x-boton>
                                    <x-boton variante="secundario" :href="route('inventario.categorias.editar', $categoria)" compacto>Editar</x-boton>
                                    <form method="POST" action="{{ route('inventario.categorias.estado', $categoria) }}"
                                        @if ($categoria->activo)
                                            data-confirmar data-confirmar-titulo="Desactivar categoría"
                                            data-confirmar-mensaje="{{ $categoria->nombre }} dejará de estar disponible para seleccionar al crear o editar productos y servicios."
                                            data-confirmar-etiqueta="Desactivar"
                                        @endif
                                    >
                                        @csrf @method('PATCH')
                                        <x-boton tipo="submit" :variante="$categoria->activo ? 'peligro' : 'exito'" compacto>
                                            {{ $categoria->activo ? 'Desactivar' : 'Activar' }}
                                        </x-boton>
                                    </form>
                                </x-grupo-acciones-fila>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="tabla-estado-vacio">
                                <x-estado-vacio mensaje="No hay categorías con estos filtros.">
                                    <x-slot:acciones>
                                        <x-boton variante="contorno" :href="route('inventario.categorias.crear')">Registrar categoría</x-boton>
                                    </x-slot:acciones>
                                </x-estado-vacio>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-tabla-responsive>
        @include('componentes.paginacion', ['paginador' => $categorias])
    </section>
@endsection
