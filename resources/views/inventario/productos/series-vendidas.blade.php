@extends('layouts.aplicacion')

@section('titulo', 'Series vendidas')

@section('contenido')

    <section class="bloque-administrativo" aria-labelledby="titulo-series-vendidas">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <div>
                <h2 id="titulo-series-vendidas">Historial de números de serie entregados</h2>
                <p>Consulta el producto, la venta y el cliente relacionados con cada serie.</p>
            </div>
        </div>
        @if ($seriesVendidas->isNotEmpty())
            <x-tabla-responsive etiqueta="Series vendidas" adaptable>
                <table class="tabla-registros tabla-adaptable">
                    <thead>
                        <tr>
                            <th scope="col">Serie</th>
                            <th scope="col">Producto</th>
                            <th scope="col">Venta</th>
                            <th scope="col">Cliente</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($seriesVendidas as $serie)
                            <tr>
                                <th scope="row" data-etiqueta="Serie"><strong>{{ $serie->numero_serie }}</strong></th>
                                <td data-etiqueta="Producto">{{ $serie->producto?->nombre }}</td>
                                <td data-etiqueta="Venta">
                                    <x-grupo-acciones-fila>
                                        <x-boton compacto variante="contorno" :href="route('ventas.detalle', $serie->partidaVenta->venta)">{{ $serie->partidaVenta->venta->folio }}</x-boton>
                                    </x-grupo-acciones-fila>
                                </td>
                                <td data-etiqueta="Cliente">{{ $serie->partidaVenta->venta->nombreClienteMostrado() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-tabla-responsive>
        @else
            <x-estado-vacio mensaje="Aún no hay series vendidas registradas.">
                <x-slot:acciones>
                    <x-boton variante="contorno" :href="route('cotizaciones.listado', ['estado' => 'aceptada'])">Consultar cotizaciones aceptadas</x-boton>
                </x-slot:acciones>
            </x-estado-vacio>
        @endif
    </section>
@endsection
