            @if ($puedeEditar && in_array($cotizacion->estado, ['borrador', 'pendiente'], true))
                <section class="bloque-administrativo seccion-formulario" aria-labelledby="titulo-agregar-concepto">
                    @php($tipoInicial = old('tipo', 'producto'))
                    <h2 id="titulo-agregar-concepto">Agregar producto o servicio</h2>
                    <p class="seccion-formulario__introduccion">
                        Selecciona un elemento del inventario, indica la cantidad y agrégalo a la cotización.
                    </p>
                    <form class="formulario-administrativo formulario-concepto" method="post" action="{{ route('cotizaciones.partidas.guardar', $cotizacion) }}">
                        @csrf
                        <div class="campo-formulario">
                            <label for="tipo-partida">Tipo de concepto</label>
                            <select id="tipo-partida" name="tipo">
                                <option value="producto" @selected($tipoInicial === 'producto')>Producto</option>
                                <option value="servicio" @selected($tipoInicial === 'servicio')>Servicio</option>
                                <option value="otro" @selected($tipoInicial === 'otro')>Otro</option>
                            </select>
                        </div>
                        <div class="campo-formulario" data-catalogo-tipo="producto" @if ($tipoInicial !== 'producto') hidden @endif>
                            <label for="producto-partida">Producto del inventario</label>
                            <select id="producto-partida" name="producto_id" @disabled($tipoInicial !== 'producto')>
                                <option value="">Selecciona un producto</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}">
                                        {{ $producto->codigo }} · {{ $producto->nombre }} · {{ $producto->categoria?->nombre ?? 'Sin categoría' }} · ${{ number_format((float) $producto->precio, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="campo-formulario" data-catalogo-tipo="servicio" @if ($tipoInicial !== 'servicio') hidden @endif>
                            <label for="servicio-partida">Servicio</label>
                            <select id="servicio-partida" name="servicio_id" @disabled($tipoInicial !== 'servicio')>
                                <option value="">Selecciona un servicio</option>
                                @foreach ($servicios as $servicio)
                                    <option value="{{ $servicio->id }}">
                                        {{ $servicio->codigo }} · {{ $servicio->nombre }} · {{ $servicio->categoria?->nombre ?? 'Sin categoría' }} · ${{ number_format((float) $servicio->precio, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="campo-formulario" data-catalogo-tipo="otro" @if ($tipoInicial !== 'otro') hidden @endif>
                            <label for="descripcion-partida">Concepto libre</label>
                            <input id="descripcion-partida" name="descripcion" value="{{ old('descripcion') }}" required @disabled($tipoInicial !== 'otro')>
                        </div>
                        <div class="campo-formulario">
                            <label for="cantidad-partida">Cantidad</label>
                            <input id="cantidad-partida" name="cantidad" type="number" step="1" min="1" inputmode="numeric" data-entero required>
                            <small>Usa números enteros.</small>
                        </div>
                        <div class="campo-formulario" data-catalogo-tipo="otro" @if ($tipoInicial !== 'otro') hidden @endif>
                            <label for="precio-partida">Precio unitario con IVA</label>
                            <input id="precio-partida" name="precio_unitario" type="number" step="0.01" min="0" value="{{ old('precio_unitario') }}" required @disabled($tipoInicial !== 'otro')>
                        </div>
                        <div class="campo-formulario">
                            <label for="descuento-partida">Descuento del concepto (%)</label>
                            <input id="descuento-partida" name="porcentaje_descuento" type="number" min="0" max="100" step="1" inputmode="numeric" data-entero value="{{ old('porcentaje_descuento', 0) }}" required>
                        </div>
                        <div class="acciones-formulario">
                            <button type="submit">Agregar concepto</button>
                        </div>
                    </form>
                </section>
            @endif
