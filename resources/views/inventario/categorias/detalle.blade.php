@extends('layouts.aplicacion')

@section('titulo', $categoria->nombre)

@section('contenido')
    <section class="bloque-administrativo" aria-labelledby="titulo-informacion-categoria">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <div class="grupo-acciones">
                <h2 id="titulo-informacion-categoria">Información de la categoría</h2>
                <x-insignia-estado :estado="$categoria->activo ? 'activa' : 'inactiva'" />
            </div>
            <div class="grupo-acciones">
                <x-boton variante="contorno" :href="route('inventario.categorias.listado')">Regresar al listado</x-boton>
                <x-boton :href="route('inventario.categorias.editar', $categoria)">Editar categoría</x-boton>
            </div>
        </div>
        <dl class="cuadricula-detalle">
            <div><dt>Nombre</dt><dd>{{ $categoria->nombre }}</dd></div>
            <div><dt>Productos relacionados</dt><dd>{{ $categoria->productos->count() }}</dd></div>
            <div><dt>Servicios relacionados</dt><dd>{{ $categoria->servicios->count() }}</dd></div>
        </dl>
    </section>

    <section class="bloque-administrativo">
        <h2>Productos</h2>
        <x-tabla-responsive etiqueta="Productos de la categoría" adaptable>
            <table class="tabla-registros tabla-adaptable">
                <thead>
                    <tr>
                        <th scope="col">SKU</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categoria->productos as $producto)
                        <tr>
                            <td data-etiqueta="SKU">{{ $producto->codigo }}</td>
                            <th scope="row" data-etiqueta="Nombre">{{ $producto->nombre }}</th>
                            <td data-etiqueta="Estado"><x-insignia-estado :estado="$producto->activo ? 'activo' : 'inactivo'" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="tabla-estado-vacio">
                                <x-estado-vacio mensaje="Esta categoría todavía no tiene productos.">
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
    </section>

    <section class="bloque-administrativo">
        <h2>Servicios</h2>
        <x-tabla-responsive etiqueta="Servicios de la categoría" adaptable>
            <table class="tabla-registros tabla-adaptable">
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categoria->servicios as $servicio)
                        <tr>
                            <td data-etiqueta="Código">{{ $servicio->codigo }}</td>
                            <th scope="row" data-etiqueta="Nombre">{{ $servicio->nombre }}</th>
                            <td data-etiqueta="Estado"><x-insignia-estado :estado="$servicio->activo ? 'activo' : 'inactivo'" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="tabla-estado-vacio">
                                <x-estado-vacio mensaje="Esta categoría todavía no tiene servicios.">
                                    <x-slot:acciones>
                                        <x-boton variante="contorno" :href="route('inventario.servicios.crear')">Registrar servicio</x-boton>
                                    </x-slot:acciones>
                                </x-estado-vacio>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-tabla-responsive>
    </section>
@endsection
