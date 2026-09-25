@extends('layouts.aplicacion')

@section('titulo', 'Ventas')

@section('contenido')
            <header class="encabezado-pagina">
                <div>
                    <h1>Ventas</h1>
                    <p>Consulta las cotizaciones aceptadas que ya fueron cerradas como venta.</p>
                </div>
                <span class="contador-registros">
                    {{ $ventas->count() }} {{ $ventas->count() === 1 ? 'venta' : 'ventas' }}
                </span>
            </header>

            <section class="bloque-administrativo" aria-labelledby="titulo-ventas">
                <h2 id="titulo-ventas">Ventas registradas</h2>
                <div class="contenedor-tabla" tabindex="0" aria-label="Listado de ventas">
                    <table class="tabla-registros">
                        <thead>
                            <tr>
                                <th scope="col">Folio</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Cotización</th>
                                <th scope="col">Método de pago</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ventas as $venta)
                                <tr>
                                    <td>
                                        <a href="{{ route('ventas.detalle', $venta) }}">
                                            {{ $venta->folio }}
                                        </a>
                                    </td>
                                    <td>{{ $venta->vendida_en->format('d/m/Y H:i') }}</td>
                                    <td>{{ $venta->nombreClienteMostrado() }}</td>
                                    <td>
                                        <a href="{{ route('cotizaciones.detalle', $venta->cotizacion) }}">
                                            {{ $venta->cotizacion->folio }}
                                        </a>
                                    </td>
                                    <td>{{ $venta->etiquetaMetodoPago() }}</td>
                                    <td>${{ number_format((float) $venta->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="estado-vacio" colspan="6">
                                        Aún no hay ventas registradas. Primero debe aceptarse y convertirse una cotización.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

@endsection
