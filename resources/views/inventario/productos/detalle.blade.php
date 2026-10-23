@extends('layouts.aplicacion')

@section('titulo', $producto->nombre)

@section('contenido')
    <section class="bloque-administrativo" aria-labelledby="titulo-informacion-producto">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <div class="grupo-acciones">
                <h2 id="titulo-informacion-producto">Información del producto</h2>
                <x-insignia-estado :estado="$producto->activo ? 'activo' : 'inactivo'" />
            </div>
            <div class="grupo-acciones">
                <x-boton variante="contorno" :href="route('inventario.productos.listado')">Regresar al listado</x-boton>
                <x-boton :href="route('inventario.productos.editar', $producto)">Editar producto</x-boton>
            </div>
        </div>

        <dl class="cuadricula-detalle">
            <div><dt>Nombre</dt><dd>{{ $producto->nombre }}</dd></div>
            <div><dt>Código/SKU</dt><dd>{{ $producto->codigo }}</dd></div>
            <div><dt>Categoría</dt><dd>{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</dd></div>
            <div><dt>Marca</dt><dd>{{ $producto->marca ?: 'No especificada' }}</dd></div>
            <div><dt>Modelo</dt><dd>{{ $producto->modelo ?: 'No especificado' }}</dd></div>
            <div><dt>Unidad</dt><dd>{{ $producto->unidad }}</dd></div>
            <div><dt>Precio con IVA</dt><dd>${{ number_format((float) $producto->precio, 2) }}</dd></div>
            <div><dt>Existencias</dt><dd>{{ $producto->existencias }}</dd></div>
            <div><dt>Números de serie</dt><dd>{{ $producto->requiere_numero_serie ? 'Se capturan al registrar la venta' : 'No requeridos' }}</dd></div>
            <div class="detalle-ancho"><dt>Descripción</dt><dd>{{ $producto->descripcion ?: 'Sin descripción' }}</dd></div>
        </dl>
    </section>

    <section class="bloque-administrativo" aria-labelledby="titulo-movimientos-existencias">
        <h2 id="titulo-movimientos-existencias">Historial de ajustes de existencias</h2>
        <x-tabla-responsive etiqueta="Ajustes manuales de existencias del producto" adaptable :vacia="$producto->movimientosExistencias->isEmpty()">
            <table class="tabla-registros tabla-adaptable">
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Responsable</th>
                        <th scope="col">Existencias anteriores</th>
                        <th scope="col">Existencias nuevas</th>
                        <th scope="col">Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($producto->movimientosExistencias as $movimiento)
                        <tr>
                            <td data-etiqueta="Fecha">{{ $movimiento->creado_en?->format('d/m/Y H:i') }}</td>
                            <td data-etiqueta="Responsable">{{ $movimiento->responsable?->nombre ?? 'Usuario no disponible' }}</td>
                            <td data-etiqueta="Existencias anteriores">{{ $movimiento->existencias_anteriores }}</td>
                            <td data-etiqueta="Existencias nuevas">{{ $movimiento->existencias_nuevas }}</td>
                            <td data-etiqueta="Motivo">{{ $movimiento->motivo }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="tabla-estado-vacio" colspan="5">
                                <x-estado-vacio mensaje="Este producto todavía no tiene ajustes manuales de existencias." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-tabla-responsive>
    </section>

    <section class="bloque-administrativo" aria-labelledby="titulo-series-vendidas-producto">
        <h2 id="titulo-series-vendidas-producto">Historial de series vendidas</h2>
        <x-tabla-responsive etiqueta="Series vendidas del producto" adaptable :vacia="$producto->seriesVendidas->isEmpty()">
            <table class="tabla-registros tabla-adaptable">
                <thead>
                    <tr>
                        <th scope="col">Número de serie</th>
                        <th scope="col">Venta</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($producto->seriesVendidas as $serie)
                        <tr>
                            <th scope="row" data-etiqueta="Número de serie">{{ $serie->numero_serie }}</th>
                            <td data-etiqueta="Venta">{{ $serie->partidaVenta?->venta?->folio ?? 'Sin referencia' }}</td>
                            <td data-etiqueta="Fecha">{{ $serie->partidaVenta?->venta?->vendida_en?->format('d/m/Y') ?? 'No disponible' }}</td>
                            <td data-etiqueta="Acción" class="acciones-tabla">
                                @if ($serie->partidaVenta?->venta)
                                    <x-boton compacto variante="contorno" :href="route('ventas.detalle', $serie->partidaVenta->venta)">Ver venta</x-boton>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="tabla-estado-vacio" colspan="4">
                                <x-estado-vacio mensaje="Este producto todavía no tiene números de serie asociados a ventas." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-tabla-responsive>
    </section>
@endsection
