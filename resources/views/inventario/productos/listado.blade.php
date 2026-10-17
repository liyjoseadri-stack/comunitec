@extends('layouts.aplicacion')

@section('titulo', 'Productos')

@section('contenido')
    <div class="grupo-acciones acciones-pagina">
        <span class="contador-registros">{{ $productos->total() }} {{ $productos->total() === 1 ? 'producto' : 'productos' }}</span>
        <x-boton :href="route('inventario.productos.crear')">Registrar producto</x-boton>
    </div>

    <section class="bloque-administrativo">
        <h2>Productos registrados</h2>
        <form class="formulario-filtros" method="GET">
            <x-campo-formulario id="buscar" nombre="buscar" etiqueta="Buscar" :valor="request('buscar')" placeholder="Nombre, SKU, marca o modelo" />
            <x-campo-formulario id="categoria" nombre="categoria" etiqueta="Categoría" tipo="select">
                <option value="">Todas</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected(request('categoria') == $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </x-campo-formulario>
            <x-campo-formulario id="estado" nombre="estado" etiqueta="Estado" tipo="select">
                <option value="">Todos</option>
                <option value="activos" @selected(request('estado') === 'activos')>Activos</option>
                <option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option>
            </x-campo-formulario>
            <x-boton tipo="submit" variante="secundario">Filtrar</x-boton>
            @if (request()->filled('buscar') || request()->filled('categoria') || request()->filled('estado'))
                <x-boton variante="contorno" :href="route('inventario.productos.listado')">Limpiar</x-boton>
            @endif
        </form>
        <x-tabla-responsive etiqueta="Productos registrados" adaptable>
            <table class="tabla-registros tabla-adaptable">
                <thead>
                    <tr>
                        <th scope="col">SKU</th>
                        <th scope="col">Producto</th>
                        <th scope="col">Series</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">Stock</th>
                        <th scope="col">Precio</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productos as $producto)
                        <tr>
                            <td data-etiqueta="SKU">{{ $producto->codigo }}</td>
                            <th scope="row" data-etiqueta="Producto"><strong>{{ $producto->nombre }}</strong></th>
                            <td data-etiqueta="Series">{{ $producto->requiere_numero_serie ? 'Requiere series' : 'No requiere series' }}</td>
                            <td data-etiqueta="Categoría">{{ $producto->categoria?->nombre }}</td>
                            <td data-etiqueta="Stock">{{ $producto->existencias }}</td>
                            <td data-etiqueta="Precio">${{ number_format((float) $producto->precio, 2) }}</td>
                            <td data-etiqueta="Estado"><x-insignia-estado :estado="$producto->activo ? 'activo' : 'inactivo'" /></td>
                            <td data-etiqueta="Acciones" class="acciones-tabla">
                                <x-grupo-acciones-fila>
                                    <x-boton compacto variante="contorno" :href="route('inventario.productos.detalle', $producto)">Ver</x-boton>
                                    <x-boton compacto variante="secundario" :href="route('inventario.productos.editar', $producto)">Editar</x-boton>
                                    <form method="POST" action="{{ route('inventario.productos.estado', $producto) }}"
                                        @if ($producto->activo)
                                            data-confirmar data-confirmar-titulo="Desactivar producto"
                                            data-confirmar-mensaje="{{ $producto->nombre }} dejará de estar disponible para agregar a nuevas cotizaciones. Las partidas existentes conservarán sus datos históricos."
                                            data-confirmar-etiqueta="Desactivar"
                                        @endif
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <x-boton compacto tipo="submit" :variante="$producto->activo ? 'peligro' : 'exito'">{{ $producto->activo ? 'Desactivar' : 'Activar' }}</x-boton>
                                    </form>
                                </x-grupo-acciones-fila>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tabla-estado-vacio">
                                <x-estado-vacio mensaje="No hay productos con estos filtros.">
                                    <x-slot:acciones>
                                        <x-boton variante="contorno" :href="route('inventario.productos.crear')">Registrar producto</x-boton>
                                    </x-slot:acciones>
                                </x-estado-vacio>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-tabla-responsive>
        @include('componentes.paginacion', ['paginador' => $productos])
    </section>
@endsection
