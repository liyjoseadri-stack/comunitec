@extends('layouts.aplicacion')

@section('titulo', 'Ventas')

@section('contenido')
            <header class="encabezado-pagina">
                <p>Consulta las cotizaciones aceptadas que ya fueron cerradas como venta.</p>
                <span class="contador-registros">
                    {{ $ventas->count() }} {{ $ventas->count() === 1 ? 'venta' : 'ventas' }}
                </span>
            </header>

            @if (auth()->user()->esAdministrador() || auth()->user()->esComercial())
                <section id="cotizaciones-aceptadas" class="bloque-administrativo" aria-labelledby="titulo-cotizaciones-aceptadas">
                    <h2 id="titulo-cotizaciones-aceptadas">Cotizaciones aceptadas por registrar</h2>
                    <p>Selecciona una cotización aceptada para confirmar el método de pago y registrar las series entregadas.</p>
                    <div class="contenedor-tabla" tabindex="0">
                        <table class="tabla-registros">
                            <thead>
                                <tr><th>Folio</th><th>Cliente</th><th>Aceptada</th><th>Total</th><th>Acción</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($cotizacionesAceptadas as $cotizacion)
                                    <tr>
                                        <td>{{ $cotizacion->folio }}</td>
                                        <td>{{ $cotizacion->cliente->nombre }}</td>
                                        <td>{{ $cotizacion->aceptada_en?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                                        <td>${{ number_format((float) $cotizacion->total, 2) }}</td>
                                        <td>
                                            <x-boton :href="route('ventas.crear', $cotizacion)" compacto>
                                                Registrar venta
                                            </x-boton>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="estado-vacio">No hay cotizaciones aceptadas pendientes de registro.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

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
                                <th scope="col">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ventas as $venta)
                                <tr>
                                    <td><strong>{{ $venta->folio }}</strong></td>
                                    <td>{{ $venta->vendida_en->format('d/m/Y H:i') }}</td>
                                    <td>{{ $venta->nombreClienteMostrado() }}</td>
                                    <td>
                                        {{ $venta->cotizacion->folio }}
                                    </td>
                                    <td>{{ $venta->etiquetaMetodoPago() }}</td>
                                    <td>${{ number_format((float) $venta->total, 2) }}</td>
                                    <td>
                                        <x-boton variante="contorno" :href="route('ventas.detalle', $venta)" compacto>
                                            Ver detalle
                                        </x-boton>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="estado-vacio" colspan="7">
                                        Aún no hay ventas registradas. Primero debe aceptarse y convertirse una cotización.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

@endsection
