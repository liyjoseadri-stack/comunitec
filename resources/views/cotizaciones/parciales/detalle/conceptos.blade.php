            <section class="bloque-administrativo" aria-labelledby="titulo-conceptos-cotizacion">
                <h2 id="titulo-conceptos-cotizacion">Conceptos de la cotización</h2>
                <x-tabla-responsive etiqueta="Productos y servicios de la cotización" class="contenedor-partidas" :vacia="$cotizacion->partidas->isEmpty()">
                    <table class="tabla-registros tabla-partidas">
                        <thead>
                            <tr>
                                <th scope="col">Tipo</th>
                                <th scope="col">Concepto</th>
                                <th scope="col">
                                    Descripción
                                </th>
                                <th scope="col">
                                    Cantidad
                                </th>
                                <th scope="col">
                                    Precio
                                </th>
                                <th scope="col">Descuento</th>
                                <th scope="col">
                                    Subtotal
                                </th>
                                <th scope="col">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cotizacion->partidas as $partida)
                                @if ($puedeEditar && in_array($cotizacion->estado, ['borrador', 'pendiente', 'aceptada'], true))
                                    <tr>
                                        <td colspan="8">
                                            <details>
                                                <summary>Editar concepto: {{ $partida->descripcion }}</summary>
                                                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.partidas.actualizar', [$cotizacion, $partida]) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="tipo" value="{{ $partida->tipo }}">
                                                    <input type="hidden" name="producto_id" value="{{ $partida->producto_id }}">
                                                    <input type="hidden" name="servicio_id" value="{{ $partida->servicio_id }}">
                                                    <label>
                                                        Descripción
                                                        <input
                                                            name="descripcion"
                                                            value="{{ $partida->descripcion }}"
                                                            required
                                                        >
                                                    </label>
                                                    <label>
                                                        Cantidad
                                                        <input
                                                            type="number"
                                                            name="cantidad"
                                                            value="{{ $partida->cantidad }}"
                                                            min="{{ $partida->tipo === 'producto' ? '1' : '0.01' }}"
                                                            step="{{ $partida->tipo === 'producto' ? '1' : '0.01' }}"
                                                            required
                                                        >
                                                    </label>
                                                    <label>
                                                        Precio unitario
                                                        <input
                                                            type="number"
                                                            name="precio_unitario"
                                                            value="{{ $partida->precio_unitario }}"
                                                            min="0"
                                                            step="0.01"
                                                            @readonly($partida->tipo !== 'otro')
                                                            required
                                                        >
                                                    </label>
                                                    <label>
                                                        Descuento del concepto (%)
                                                        <input
                                                            type="number"
                                                            name="porcentaje_descuento"
                                                            value="{{ $partida->porcentaje_descuento }}"
                                                            min="0"
                                                            max="100"
                                                            step="0.01"
                                                            required
                                                        >
                                                    </label>
                                                    <button type="submit">
                                                        Guardar cambios
                                                    </button>
                                                </form>
                                            </details>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td><span class="texto-tipo-concepto">{{ ucfirst($partida->tipo) }}</span></td>
                                    <th scope="row">
                                        {{ $partida->nombreHistorico() }}
                                        @if ($partida->codigoHistorico())
                                            <small class="detalle-tabla">{{ $partida->codigoHistorico() }}</small>
                                        @endif
                                    </th>
                                    <td>
                                        {{ $partida->descripcion }}
                                    </td>
                                    <td>
                                        {{ $partida->cantidad }}
                                    </td>
                                    <td>
                                        ${{ $partida->precio_unitario }}
                                    </td>
                                    <td>
                                        @if ((float) $partida->porcentaje_descuento > 0)
                                            {{ number_format((float) $partida->porcentaje_descuento, 2) }}% en concepto
                                        @elseif ((float) $cotizacion->porcentaje_descuento > 0)
                                            {{ number_format((float) $cotizacion->porcentaje_descuento, 2) }}% global
                                        @else
                                            Sin descuento
                                        @endif
                                    </td>
                                    <td>
                                        ${{ $partida->subtotal }}
                                    </td>
                                    <td>
                                        <x-grupo-acciones-fila>
                                            @if ($puedeEditar && $cotizacion->estado === 'borrador')
                                                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.partidas.eliminar', [$cotizacion, $partida]) }}"
                                                    data-confirmar data-confirmar-titulo="Quitar concepto"
                                                    data-confirmar-mensaje="Se quitará {{ $partida->descripcion }} de la cotización {{ $cotizacion->folio }} y se recalculará el total."
                                                    data-confirmar-etiqueta="Quitar concepto">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-boton variante="peligro" tipo="submit" compacto>
                                                        Quitar
                                                    </x-boton>
                                                </form>
                                            @endif
                                        </x-grupo-acciones-fila>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="tabla-estado-vacio">
                                        <x-estado-vacio mensaje="Esta cotización todavía no tiene conceptos.">
                                            <x-slot:acciones>
                                                @if ($puedeEditar && in_array($cotizacion->estado, ['borrador', 'pendiente', 'aceptada'], true))
                                                    <x-boton variante="contorno" href="#tipo-partida">Agregar concepto</x-boton>
                                                @endif
                                            </x-slot:acciones>
                                        </x-estado-vacio>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-tabla-responsive>
                <p><strong>Total: ${{ $cotizacion->total }}</strong></p>
            </section>
