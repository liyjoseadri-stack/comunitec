            <section class="bloque-administrativo" aria-labelledby="titulo-conceptos-cotizacion">
                @foreach ($cotizacion->configuracionesPc as $configuracionPc)
                    @php
                        $partidasPc = $cotizacion->partidas
                            ->where('configuracion_pc_cotizacion_id', $configuracionPc->id)
                            ->sortBy(fn ($partidaPc) => array_flip(\App\Models\Producto::tiposComponentesPc())[$partidaPc->tipo_componente_pc] ?? 99);
                        $tiposPrincipalesPc = [
                            \App\Models\Producto::TIPO_COMPONENTE_PROCESADOR,
                            \App\Models\Producto::TIPO_COMPONENTE_PLACA_MADRE,
                            \App\Models\Producto::TIPO_COMPONENTE_MEMORIA_RAM,
                            \App\Models\Producto::TIPO_COMPONENTE_ALMACENAMIENTO,
                            \App\Models\Producto::TIPO_COMPONENTE_TARJETA_GRAFICA,
                            \App\Models\Producto::TIPO_COMPONENTE_FUENTE_PODER,
                            \App\Models\Producto::TIPO_COMPONENTE_GABINETE,
                        ];
                        $cantidadPc = max((int) $configuracionPc->cantidad, 1);
                    @endphp
                    <article class="cotizacion-pc-resumen">
                        <div class="encabezado-seccion encabezado-seccion--acciones">
                            <div>
                                <p class="ceja-seccion">PC armada</p>
                                <h3>{{ $configuracionPc->nombre }}</h3>
                                @if ($configuracionPc->descripcion)
                                    <p>{{ $configuracionPc->descripcion }}</p>
                                @endif
                            </div>
                            <div>
                                <strong>{{ $cantidadPc }} PC{{ $cantidadPc === 1 ? '' : 's' }}</strong>
                                <strong>${{ number_format((float) $partidasPc->sum('subtotal'), 2) }}</strong>
                            </div>
                        </div>
                        <ul class="cotizacion-pc-resumen__componentes">
                            @foreach ($partidasPc->filter(fn ($partidaPc) => in_array($partidaPc->tipo_componente_pc, $tiposPrincipalesPc, true)) as $partidaPc)
                                <li>
                                    <span><strong>{{ \App\Models\Producto::etiquetaTipoComponentePc($partidaPc->tipo_componente_pc) }}:</strong> {{ number_format((float) $partidaPc->cantidad / $cantidadPc, 0) }} × {{ $partidaPc->nombreHistorico() }} por PC</span>
                                    <strong>${{ number_format((float) $partidaPc->subtotal, 2) }}</strong>
                                </li>
                            @endforeach
                            @if ($partidasPc->reject(fn ($partidaPc) => in_array($partidaPc->tipo_componente_pc, $tiposPrincipalesPc, true))->isNotEmpty())
                                <li class="cotizacion-pc-resumen__separador"><strong>Extras opcionales</strong></li>
                                @foreach ($partidasPc->reject(fn ($partidaPc) => in_array($partidaPc->tipo_componente_pc, $tiposPrincipalesPc, true)) as $partidaPc)
                                    <li>
                                        <span><strong>{{ \App\Models\Producto::etiquetaTipoComponentePc($partidaPc->tipo_componente_pc) }}:</strong> {{ number_format((float) $partidaPc->cantidad / $cantidadPc, 0) }} × {{ $partidaPc->nombreHistorico() }} por PC</span>
                                        <strong>${{ number_format((float) $partidaPc->subtotal, 2) }}</strong>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                        <p class="texto-ayuda">Esta PC se administra como un solo concepto. Sus componentes se reservan individualmente al aceptar la cotización.</p>
                    </article>
                @endforeach
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
                            @php($partidasSinPc = $cotizacion->partidas->whereNull('configuracion_pc_cotizacion_id'))
                            @forelse ($partidasSinPc as $partida)
                                @if (! $partida->configuracion_pc_cotizacion_id && $puedeEditar && in_array($cotizacion->estado, ['borrador', 'pendiente', 'aceptada'], true))
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
                                                    @if ($partida->tipo === 'otro')
                                                        <label>
                                                            Concepto libre
                                                            <input
                                                                name="descripcion"
                                                                value="{{ $partida->descripcion }}"
                                                                required
                                                            >
                                                        </label>
                                                    @endif
                                                    <label>
                                                        Cantidad
                                                        <input
                                                            type="number"
                                                            name="cantidad"
                                                            value="{{ $partida->cantidad }}"
                                                            min="1"
                                                            step="1"
                                                            inputmode="numeric"
                                                            data-entero
                                                            required
                                                        >
                                                    </label>
                                                    @if ($partida->tipo === 'otro')
                                                        <label>
                                                            Precio unitario
                                                            <input
                                                                type="number"
                                                                name="precio_unitario"
                                                                value="{{ $partida->precio_unitario }}"
                                                                min="0"
                                                                step="0.01"
                                                                required
                                                            >
                                                        </label>
                                                    @endif
                                                    <label>
                                                        Descuento del concepto (%)
                                                        <input
                                                            type="number"
                                                            name="porcentaje_descuento"
                                                            value="{{ (float) $partida->porcentaje_descuento }}"
                                                            min="0"
                                                            max="100"
                                                            step="1"
                                                            inputmode="numeric"
                                                            data-entero
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
                                        @if ($partida->configuracionPc)
                                            <small class="detalle-tabla">PC: {{ $partida->configuracionPc->nombre }} · {{ \App\Models\Producto::etiquetaTipoComponentePc($partida->tipo_componente_pc) }}</small>
                                        @endif
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
                                            @if (! $partida->configuracion_pc_cotizacion_id && $puedeEditar && $cotizacion->estado === 'borrador')
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
                                @if ($cotizacion->configuracionesPc->isEmpty())
                                <tr>
                                    <td colspan="8" class="tabla-estado-vacio">
                                        <x-estado-vacio mensaje="Esta cotización todavía no tiene conceptos.">
                                            <x-slot:acciones>
                                                @if ($puedeEditar && in_array($cotizacion->estado, ['borrador', 'pendiente'], true))
                                                    <x-boton variante="contorno" href="#tipo-partida">Agregar concepto</x-boton>
                                                @endif
                                            </x-slot:acciones>
                                        </x-estado-vacio>
                                    </td>
                                </tr>
                                @endif
                            @endforelse
                        </tbody>
                    </table>
                </x-tabla-responsive>
                <p><strong>Total: ${{ $cotizacion->total }}</strong></p>
            </section>
