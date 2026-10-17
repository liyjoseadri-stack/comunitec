            <section class="bloque-administrativo" aria-labelledby="titulo-resumen-cotizacion">
                <h2 id="titulo-resumen-cotizacion">Resumen de la cotización</h2>
                <dl class="datos-operacion">
                    <div>
                        <dt>Cliente</dt>
                        <dd>{{ $cotizacion->cliente->nombre }}</dd>
                    </div>
                    <div>
                        <dt>Estado</dt>
                        <dd>
                            <x-insignia-estado :estado="$cotizacion->estado" :etiqueta="$cotizacion->etiquetaEstado()" />
                            @if ($cotizacion->venta)
                                <span class="detalle-tabla">Convertida en venta</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Área solicitante</dt>
                        <dd>{{ $cotizacion->area_solicitante ?: 'No especificada' }}</dd>
                    </div>
                    <div>
                        <dt>Descuento global</dt>
                        <dd>{{ number_format((float) $cotizacion->porcentaje_descuento, 2) }}%</dd>
                    </div>
                    <div>
                        <dt>Fecha de emisión</dt>
                        <dd>
                            @php($fechaEmision = $cotizacion->enviada_en ?? $cotizacion->creado_en)
                            <time datetime="{{ $fechaEmision->toIso8601String() }}">
                                {{ $fechaEmision->format('d/m/Y H:i') }}
                            </time>
                        </dd>
                    </div>
                    @if ($cotizacion->vence_en)
                        <div>
                            <dt>Vigente hasta</dt>
                            <dd><time datetime="{{ $cotizacion->vence_en->toIso8601String() }}">{{ $cotizacion->vence_en->format('d/m/Y H:i') }}</time></dd>
                        </div>
                    @endif
                    @if ($cotizacion->aceptada_en)
                        <div>
                            <dt>Fecha de aceptación</dt>
                            <dd><time datetime="{{ $cotizacion->aceptada_en->toIso8601String() }}">{{ $cotizacion->aceptada_en->format('d/m/Y H:i') }}</time></dd>
                        </div>
                    @endif
                    @if ($cotizacion->entrega_limite_en)
                        <div>
                            <dt>Fecha límite de entrega</dt>
                            <dd><time datetime="{{ $cotizacion->entrega_limite_en->toIso8601String() }}">{{ $cotizacion->entrega_limite_en->format('d/m/Y H:i') }}</time></dd>
                        </div>
                    @endif
                    @if ($cotizacion->venta)
                        <div>
                            <dt>Venta relacionada</dt>
                            <dd>
                                <x-boton variante="contorno" :href="route('ventas.detalle', $cotizacion->venta)" compacto>
                                    Ver venta
                                </x-boton>
                                <small class="detalle-tabla">{{ $cotizacion->venta->folio }}</small>
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>
            @if (in_array($cotizacion->estado, ['borrador', 'pendiente'], true) && $faltantes->isNotEmpty())
                <section role="alert">
                    <h2>
                        Advertencia de inventario
                    </h2>
                    <ul>
                        @foreach ($faltantes as $faltante)
                            <li>
                                {{ $faltante['descripcion'] }}: se cotizaron {{ $faltante['solicitado'] }} piezas y hay {{ $faltante['disponible'] }} disponibles.
                            </li>
                        @endforeach
                    </ul>
                    <p>
                        El borrador puede guardarse, pero no podrá aceptarse hasta contar con las piezas necesarias.
                    </p>
                </section>
            @endif
            @if ($puedeEditar && in_array($cotizacion->estado, ['borrador', 'pendiente', 'aceptada'], true))
                <details>
                    <summary>
                        Editar datos de la cotización
                    </summary>
                    <form class="formulario-administrativo formulario-dos-columnas" method="post" action="{{ route('cotizaciones.actualizar', $cotizacion) }}">
                        @csrf
                        @method('PUT')
                        <label>
                            Cliente
                            <select name="cliente_id" required>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" @selected(old('cliente_id', $cotizacion->cliente_id) == $cliente->id)>{{ $cliente->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            Área solicitante
                            <input name="area_solicitante" value="{{ old('area_solicitante', $cotizacion->area_solicitante) }}" maxlength="255">
                        </label>
                        <label>
                            Descuento global (%)
                            <input type="number" name="porcentaje_descuento" value="{{ old('porcentaje_descuento', $cotizacion->porcentaje_descuento) }}" min="0" max="10" step="0.01">
                        </label>
                        <label class="campo-formulario--ancho">
                            Notas generales o términos comerciales
                            <textarea name="notas" maxlength="3000">{{ old('notas', $cotizacion->notas) }}</textarea>
                        </label>
                        <p class="ayuda-campo campo-formulario--ancho">
                            Usa 0 para no aplicar descuento, o un porcentaje entre 5 y 10.
                        </p>
                        <div class="acciones-formulario campo-formulario--ancho">
                            <button type="submit">Guardar datos de la cotización</button>
                        </div>
                    </form>
                </details>
            @endif
