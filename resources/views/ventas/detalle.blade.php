@extends('layouts.aplicacion')

@section('titulo', 'Venta '.$venta->folio)

@section('contenido')
            <section class="bloque-administrativo" aria-labelledby="titulo-datos-venta">
                <div class="encabezado-seccion encabezado-seccion--acciones">
                    <div>
                        <h2 id="titulo-datos-venta">Información histórica de la venta</h2>
                        <p>Estos datos corresponden al momento en que se registró la operación.</p>
                    </div>
                    <div class="grupo-acciones">
                        <x-boton variante="contorno" :href="route('ventas.listado')">Volver al historial</x-boton>
                        <x-boton :href="route('ventas.pdf', $venta)">Descargar PDF</x-boton>
                    </div>
                </div>
                <dl class="datos-operacion datos-operacion--venta">
                    <div>
                        <dt>Fecha de venta</dt>
                        <dd>{{ $venta->vendida_en->format('d/m/Y H:i') }}</dd>
                    </div>
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
                        <dd>
                            <x-boton variante="contorno" :href="route('cotizaciones.detalle', $venta->cotizacion)" compacto>
                                {{ $venta->cotizacion->folio }}
                            </x-boton>
                        </dd>
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
                <div class="encabezado-seccion">
                    <div>
                        <h2 id="titulo-partidas-venta">Productos y servicios vendidos</h2>
                        <p>Los conceptos, precios y series se muestran con la información histórica de esta venta.</p>
                    </div>
                </div>
                <x-tabla-responsive etiqueta="Productos y servicios de la venta">
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
                                <th scope="col">Números de serie entregados</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($venta->partidas as $partida)
                                <tr>
                                    <td data-etiqueta="Tipo"><span class="texto-tipo-concepto">{{ ucfirst($partida->tipo) }}</span></td>
                                    <th scope="row" data-etiqueta="Concepto">{{ $partida->nombreHistorico() }}</th>
                                    <td data-etiqueta="Descripción">{{ $partida->descripcion ?: 'Sin descripción adicional' }}</td>
                                    <td data-etiqueta="Cantidad">{{ number_format((float) $partida->cantidad, 2) }}</td>
                                    <td data-etiqueta="Precio unitario">${{ number_format((float) $partida->precio_unitario, 2) }}</td>
                                    <td data-etiqueta="Descuento">{{ (float) $partida->porcentaje_descuento > 0 ? number_format((float) $partida->porcentaje_descuento, 2).'%' : 'Sin descuento' }}</td>
                                    <td data-etiqueta="Subtotal">${{ number_format((float) $partida->subtotal, 2) }}</td>
                                    <td data-etiqueta="Números de serie">
                                        @if ($partida->piezas->isNotEmpty())
                                            <ul class="lista-series-venta">
                                                @foreach ($partida->piezas as $pieza)
                                                    <li><code>{{ $pieza->numero_serie }}</code></li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="texto-secundario">No aplica</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-tabla-responsive>

                <h3 class="titulo-resumen-importes">Resumen de importes</h3>
                <table class="resumen-importes" aria-label="Resumen de importes de la venta">
                    <tr>
                        <th scope="row">Subtotal</th>
                        <td>${{ number_format((float) $venta->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Descuento global</th>
                        <td>
                            {{ number_format((float) $venta->porcentaje_descuento, 2) }}%
                            @if ((float) $venta->porcentaje_descuento > 0)
                                (−${{ number_format((float) $venta->subtotal - (float) $venta->total, 2) }})
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Total</th>
                        <td>${{ number_format((float) $venta->total, 2) }}</td>
                    </tr>
                </table>
            </section>

@endsection
