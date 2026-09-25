@extends('layouts.aplicacion')

@section('titulo', 'Productos')

@section('contenido')
    @include('componentes.errores-validacion')
    @if (session('success'))
        <p class="mensaje-exito">{{ session('success') }}</p>
    @endif

    <section id="nuevo-producto" class="bloque-administrativo">
        <h2>Registrar producto</h2>
        <form class="formulario-administrativo formulario-catalogo" method="POST" action="{{ route('inventario.productos.guardar') }}">
            @csrf
            <div class="campo-formulario">
                <label for="categoria_id">Categoría</label>
                <select id="categoria_id" name="categoria_id" required>
                    <option value="">Selecciona</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="campo-formulario">
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre" value="{{ old('nombre') }}" required>
            </div>
            <div class="campo-formulario">
                <label for="codigo">Código/SKU</label>
                <input id="codigo" name="codigo" value="{{ old('codigo') }}" required>
            </div>
            <div class="campo-formulario campo-formulario--ancho">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion">{{ old('descripcion') }}</textarea>
            </div>
            <div class="campo-formulario">
                <label for="marca">Marca</label>
                <input id="marca" name="marca" value="{{ old('marca') }}">
            </div>
            <div class="campo-formulario">
                <label for="modelo">Modelo</label>
                <input id="modelo" name="modelo" value="{{ old('modelo') }}">
            </div>
            <div class="campo-formulario">
                <label for="unidad">Unidad</label>
                <input id="unidad" name="unidad" value="{{ old('unidad', 'pieza') }}" required>
            </div>
            <div class="campo-formulario">
                <label for="precio">Precio con IVA</label>
                <input id="precio" type="number" step="0.01" min="0" name="precio" value="{{ old('precio') }}" required>
            </div>
            <div class="campo-formulario">
                <label for="existencias">Existencias</label>
                <input id="existencias" type="number" min="0" name="existencias" value="{{ old('existencias', 0) }}" required>
            </div>
            <label class="campo-formulario">
                <span>Números de serie</span>
                <span><input type="checkbox" name="requiere_numero_serie" value="1" @checked(old('requiere_numero_serie'))> Requiere serie al vender</span>
            </label>
            <x-boton tipo="submit">Guardar producto</x-boton>
        </form>
    </section>

    <section class="bloque-administrativo">
        <h2>Productos registrados</h2>
        <form class="formulario-filtros" method="GET">
            <div class="campo-formulario">
                <label for="buscar">Buscar</label>
                <input id="buscar" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, SKU, marca o modelo">
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
                <thead><tr><th>SKU</th><th>Producto</th><th>Categoría</th><th>Stock</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse ($productos as $producto)
                        <tr>
                            <td>{{ $producto->codigo }}</td>
                            <td><strong>{{ $producto->nombre }}</strong>@if ($producto->requiere_numero_serie)<small class="detalle-tabla">Requiere series</small>@endif</td>
                            <td>{{ $producto->categoria?->nombre }}</td>
                            <td>{{ $producto->existencias }}</td>
                            <td>${{ number_format((float) $producto->precio, 2) }}</td>
                            <td><x-insignia-estado :estado="$producto->activo ? 'activo' : 'inactivo'" /></td>
                            <td class="acciones-tabla">
                                <x-boton compacto variante="contorno" :href="route('inventario.productos.detalle', $producto)">Ver/Editar</x-boton>
                                <form method="POST" action="{{ route('inventario.productos.estado', $producto) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-boton compacto tipo="submit" :variante="$producto->activo ? 'peligro' : 'exito'">{{ $producto->activo ? 'Desactivar' : 'Activar' }}</x-boton>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="estado-vacio">No hay productos con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('componentes.paginacion', ['paginador' => $productos])
    </section>

    <section id="series-vendidas" class="bloque-administrativo">
        <h2>Series vendidas recientemente</h2>
        @if ($seriesVendidas->isNotEmpty())
            <div class="contenedor-tabla" tabindex="0">
                <table class="tabla-registros">
                    <thead><tr><th>Serie</th><th>Producto</th><th>Venta</th><th>Cliente</th></tr></thead>
                    <tbody>
                        @foreach ($seriesVendidas as $serie)
                            <tr>
                                <td><strong>{{ $serie->numero_serie }}</strong></td>
                                <td>{{ $serie->producto?->nombre }}</td>
                                <td><x-boton compacto variante="contorno" :href="route('ventas.detalle', $serie->partidaVenta->venta)">{{ $serie->partidaVenta->venta->folio }}</x-boton></td>
                                <td>{{ $serie->partidaVenta->venta->nombreClienteMostrado() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="estado-vacio">Las series se registran al convertir una cotización aceptada en venta.</p>
        @endif
    </section>
@endsection
