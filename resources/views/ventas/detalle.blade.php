@extends('layouts.aplicacion')

@section('titulo', 'Venta '.$venta->folio)

@section('contenido')
            <header class="encabezado-pagina">
                <p>Registrada el {{ $venta->vendida_en->format('d/m/Y H:i') }}.</p>
                <div class="grupo-acciones">
                    <span class="insignia-tipo">Venta cerrada</span>
                    <x-boton variante="contorno" :href="route('ventas.pdf', $venta)">Descargar PDF</x-boton>
                </div>
            </header>

            @if (session('success'))
                <p role="status">{{ session('success') }}</p>
            @endif

            <section class="bloque-administrativo" aria-labelledby="titulo-datos-venta">
                <h2 id="titulo-datos-venta">Datos de la venta</h2>
                <dl class="datos-operacion">
                    <div>
                        <dt>Cliente</dt>
                        <dd>{{ $venta->nombreClienteMostrado() }}</dd>
                    </div>
                    <div>
                        <dt>RFC</dt>
                        <dd>{{ $venta->rfc_cliente }}</dd>
                    </div>
                    <div>
                        <dt>Correo</dt>
                        <dd>{{ $venta->correo_cliente }}</dd>
                    </div>
                    <div>
                        <dt>Teléfono</dt>
                        <dd>{{ $venta->telefono_cliente }}</dd>
                    </div>
                    <div>
                        <dt>Dirección</dt>
                        <dd>
                            {{ $venta->direccion_cliente }}, C.P. {{ $venta->codigo_postal_cliente }}
                        </dd>
                    </div>
                    <div>
                        <dt>Cotización de origen</dt>
                        <dd>{{ $venta->cotizacion->folio }}</dd>
                    </div>
                    <div>
                        <dt>Responsable</dt>
                        <dd>{{ $venta->nombreResponsableMostrado() }}</dd>
                    </div>
                    <div>
                        <dt>Correo del responsable</dt>
                        <dd>{{ $venta->correo_responsable }}</dd>
                    </div>
                    <div>
                        <dt>Método de pago</dt>
                        <dd>{{ $venta->etiquetaMetodoPago() }}</dd>
                    </div>
                </dl>
            </section>

            <section class="bloque-administrativo" aria-labelledby="titulo-partidas-venta">
                <h2 id="titulo-partidas-venta">Productos y servicios vendidos</h2>
                <div class="contenedor-tabla" tabindex="0" aria-label="Productos y servicios de la venta">
                    <table class="tabla-registros tabla-catalogo">
                        <thead>
                            <tr>
                                <th scope="col">Tipo</th>
                                <th scope="col">Concepto</th>
                                <th scope="col">Descripción</th>
                                <th scope="col">Cantidad</th>
                                <th scope="col">Precio unitario</th>
                                <th scope="col">Descuento</th>
                                <th scope="col">Subtotal</th>
                                <th scope="col">Series entregadas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($venta->partidas as $partida)
                                <tr>
                                    <td><span class="insignia-tipo">{{ ucfirst($partida->tipo) }}</span></td>
                                    <td>{{ $partida->articulo?->nombre ?? 'Concepto libre' }}</td>
                                    <td>{{ $partida->descripcion }}</td>
                                    <td>{{ number_format((float) $partida->cantidad, 2) }}</td>
                                    <td>${{ number_format((float) $partida->precio_unitario, 2) }}</td>
                                    <td>{{ (float) $venta->porcentaje_descuento > 0 ? number_format((float) $venta->porcentaje_descuento, 2).'% global' : 'Sin descuento' }}</td>
                                    <td>${{ number_format((float) $partida->subtotal, 2) }}</td>
                                    <td>
                                        {{ $partida->piezas->pluck('numero_serie')->join(', ') ?: 'No aplica' }}
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
                        <td>{{ number_format((float) $venta->porcentaje_descuento, 2) }}%</td>
                    </tr>
                    <tr>
                        <th>Total</th>
                        <td>${{ number_format((float) $venta->total, 2) }}</td>
                    </tr>
                </table>
            </section>

@endsection
