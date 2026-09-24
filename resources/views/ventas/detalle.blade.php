<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $venta->folio }} | Comunitec</title>
        <link rel="stylesheet" href="{{ asset('css/navegacion.css') }}">
        <link rel="stylesheet" href="{{ asset('css/administracion.css') }}">
    </head>
    <body>
        @include('componentes.navegacion')

        <main class="pagina-administrativa">
            <header class="encabezado-pagina">
                <div>
                    <h1>Venta {{ $venta->folio }}</h1>
                    <p>Registrada el {{ $venta->sold_at->format('d/m/Y H:i') }}.</p>
                </div>
                <span class="insignia-tipo">Venta cerrada</span>
            </header>

            @if (session('success'))
                <p role="status">{{ session('success') }}</p>
            @endif

            <section class="bloque-administrativo" aria-labelledby="titulo-datos-venta">
                <h2 id="titulo-datos-venta">Datos de la venta</h2>
                <dl class="datos-operacion">
                    <div>
                        <dt>Cliente</dt>
                        <dd>{{ $venta->customer_name }}</dd>
                    </div>
                    <div>
                        <dt>RFC</dt>
                        <dd>{{ $venta->customer_rfc }}</dd>
                    </div>
                    <div>
                        <dt>Correo</dt>
                        <dd>{{ $venta->customer_email }}</dd>
                    </div>
                    <div>
                        <dt>Teléfono</dt>
                        <dd>{{ $venta->customer_phone }}</dd>
                    </div>
                    <div>
                        <dt>Dirección</dt>
                        <dd>
                            {{ $venta->customer_address }}, C.P. {{ $venta->customer_postal_code }}
                        </dd>
                    </div>
                    <div>
                        <dt>Cotización de origen</dt>
                        <dd>
                            <a href="{{ route('cotizaciones.detalle', $venta->cotizacion) }}">
                                {{ $venta->cotizacion->folio }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt>Responsable</dt>
                        <dd>{{ $venta->responsible_name }}</dd>
                    </div>
                    <div>
                        <dt>Correo del responsable</dt>
                        <dd>{{ $venta->responsible_email }}</dd>
                    </div>
                    <div>
                        <dt>Método de pago</dt>
                        <dd>{{ $venta->etiquetaMetodoPago() }}</dd>
                    </div>
                </dl>
            </section>

            <section class="bloque-administrativo" aria-labelledby="titulo-partidas-venta">
                <h2 id="titulo-partidas-venta">Partidas entregadas</h2>
                <div class="contenedor-tabla" tabindex="0" aria-label="Partidas de la venta">
                    <table class="tabla-registros tabla-catalogo">
                        <thead>
                            <tr>
                                <th scope="col">Descripción</th>
                                <th scope="col">Cantidad</th>
                                <th scope="col">Precio unitario</th>
                                <th scope="col">Subtotal</th>
                                <th scope="col">Series entregadas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($venta->partidas as $partida)
                                <tr>
                                    <td>{{ $partida->description }}</td>
                                    <td>{{ number_format((float) $partida->quantity, 2) }}</td>
                                    <td>${{ number_format((float) $partida->unit_price, 2) }}</td>
                                    <td>${{ number_format((float) $partida->subtotal, 2) }}</td>
                                    <td>
                                        {{ $partida->piezas->pluck('serial_number')->join(', ') ?: 'No aplica' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <table class="resumen-importes">
                    <tr>
                        <th>Subtotal</th>
                        <td>${{ number_format((float) $venta->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Descuento global</th>
                        <td>{{ number_format((float) $venta->discount_percent, 2) }}%</td>
                    </tr>
                    <tr>
                        <th>Total</th>
                        <td>${{ number_format((float) $venta->total, 2) }}</td>
                    </tr>
                </table>
            </section>
        </main>
    </body>
</html>
