@extends('layouts.aplicacion')

@section('titulo', 'Registrar venta')

@section('contenido')
    @php
        $partidasConSerie = $cotizacion->partidas->filter(
            fn ($partida) => $partida->tipo === 'producto'
                && ($partida->requiere_numero_serie ?? $partida->articulo?->requiere_numero_serie)
        )->values();
    @endphp

    <header class="encabezado-pagina encabezado-pagina--acciones">
        <div class="grupo-acciones">
            <x-insignia-estado estado="aceptada" etiqueta="Cotización aceptada" />
            <x-boton variante="contorno" :href="route('cotizaciones.detalle', $cotizacion)">Ver cotización</x-boton>
        </div>
    </header>

    <div class="flujo-registro-venta">
        <section class="bloque-administrativo" aria-labelledby="titulo-resumen-origen">
            <div class="encabezado-seccion encabezado-seccion--venta">
                <div>
                    <p class="sobrelinea">Información de consulta</p>
                    <h2 id="titulo-resumen-origen">Datos heredados de la cotización</h2>
                    <p>Estos datos se conservarán en la venta tal como fueron aceptados.</p>
                </div>
                <span class="insignia-tipo">Solo lectura</span>
            </div>

            <dl class="datos-operacion datos-operacion--venta">
                <div><dt>Cliente</dt><dd>{{ $cotizacion->cliente->nombre }}</dd></div>
                <div><dt>Folio de cotización</dt><dd>{{ $cotizacion->folio }}</dd></div>
                <div><dt>Descuento global</dt><dd>{{ number_format((float) $cotizacion->porcentaje_descuento, 2) }}%</dd></div>
                <div><dt>Total aceptado</dt><dd>${{ number_format((float) $cotizacion->total, 2) }}</dd></div>
            </dl>

            <x-tabla-responsive etiqueta="Conceptos de la cotización aceptada">
                <table class="tabla-registros tabla-venta-origen">
                    <thead><tr><th scope="col">Tipo</th><th scope="col">Concepto</th><th scope="col">Descripción</th><th scope="col">Cantidad</th><th scope="col">Precio unitario</th><th scope="col">Descuento</th><th scope="col">Importe</th></tr></thead>
                    <tbody>
                        @foreach ($cotizacion->partidas as $partida)
                            <tr>
                                <td data-etiqueta="Tipo"><span class="insignia-tipo">{{ ucfirst($partida->tipo) }}</span></td>
                                <th scope="row" data-etiqueta="Concepto">{{ $partida->nombreHistorico() }}</th>
                                <td data-etiqueta="Descripción">{{ $partida->descripcion }}</td>
                                <td data-etiqueta="Cantidad">{{ number_format((float) $partida->cantidad, 2) }}</td>
                                <td data-etiqueta="Precio unitario">${{ number_format((float) $partida->precio_unitario, 2) }}</td>
                                <td data-etiqueta="Descuento">{{ (float) $partida->porcentaje_descuento > 0 ? number_format((float) $partida->porcentaje_descuento, 2).'%' : 'Sin descuento por concepto' }}</td>
                                <td data-etiqueta="Importe">${{ number_format((float) $partida->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-tabla-responsive>
        </section>

        <section class="bloque-administrativo bloque-captura-venta" aria-labelledby="titulo-datos-cierre">
            <div class="encabezado-seccion encabezado-seccion--venta">
                <div>
                    <p class="sobrelinea">Paso final</p>
                    <h2 id="titulo-datos-cierre">Datos que debes capturar <span class="contador-titulo">{{ $partidasConSerie->count() }}</span></h2>
                    <p>Confirma el método de pago y registra las series físicas que se entregarán.</p>
                </div>
            </div>

            <form class="formulario-administrativo formulario-venta" method="post" action="{{ route('ventas.guardar', $cotizacion) }}">
                @csrf
                <fieldset class="grupo-captura-venta grupo-captura-venta--pago">
                    <legend>Método de pago</legend>
                    <p class="grupo-captura-venta__ayuda">Este dato identifica cómo se registró administrativamente la venta.</p>
                    <div class="cuadricula-pago-venta">
                        <x-campo-formulario id="metodo-pago" nombre="metodo_pago" etiqueta="Método de pago" tipo="select" required>
                            <option value="">Selecciona un método</option>
                            @foreach (['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta', 'otro' => 'Otro'] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(old('metodo_pago') === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </x-campo-formulario>
                        <x-campo-formulario id="detalle-metodo-pago" nombre="detalle_metodo_pago" etiqueta="Descripción del método de pago" ayuda="Solo es obligatoria cuando eliges Otro." :valor="old('detalle_metodo_pago')" maxlength="255" />
                    </div>
                </fieldset>

                <section class="captura-series-venta" aria-labelledby="titulo-series-venta">
                    <div class="captura-series-venta__encabezado">
                        <h3 id="titulo-series-venta">Números de serie</h3>
                        @if ($partidasConSerie->isNotEmpty())
                            <p id="instrucciones-series-venta">Todos los campos son obligatorios. Solo puedes registrar la cantidad indicada para cada producto y una serie no puede repetirse.</p>
                        @else
                            <p>Esta venta no contiene productos configurados para solicitar número de serie.</p>
                        @endif
                    </div>

                    @foreach ($partidasConSerie as $indiceProducto => $partida)
                        <fieldset class="producto-series-venta" aria-labelledby="producto-serie-{{ $partida->id }}" aria-describedby="instrucciones-series-venta">
                            <legend id="producto-serie-{{ $partida->id }}">
                                <span class="producto-series-venta__numero">Producto {{ $indiceProducto + 1 }}</span>
                                <span>{{ $partida->nombreHistorico() }}</span>
                            </legend>
                            <div class="producto-series-venta__resumen">
                                @if ($partida->codigo_concepto)<span>Código: <strong>{{ $partida->codigo_concepto }}</strong></span>@endif
                                <span>Cantidad a entregar: <strong>{{ (int) $partida->cantidad }}</strong></span>
                            </div>
                            @if ($partida->descripcion)<p class="producto-series-venta__descripcion">{{ $partida->descripcion }}</p>@endif
                            <div class="cuadricula-series-venta">
                                @for ($indice = 0; $indice < (int) $partida->cantidad; $indice++)
                                    <x-campo-formulario
                                        id="serie-{{ $partida->id }}-{{ $indice }}"
                                        nombre="series[{{ $partida->id }}][]"
                                        clave-error="series.{{ $partida->id }}.{{ $indice }}"
                                        etiqueta="Unidad {{ $indice + 1 }} de {{ (int) $partida->cantidad }} · Número de serie"
                                        :valor="old('series.'.$partida->id.'.'.$indice)"
                                        maxlength="255" autocomplete="off" spellcheck="false" required
                                    />
                                @endfor
                            </div>
                        </fieldset>
                    @endforeach
                </section>

                <p class="nota-captura-venta">Los servicios y los productos configurados sin número de serie se registran automáticamente con los datos de la cotización.</p>
                <div class="acciones-formulario acciones-formulario--venta">
                    <x-boton variante="contorno" :href="route('ventas.listado')">Cancelar</x-boton>
                    <x-boton tipo="submit">Registrar venta</x-boton>
                </div>
            </form>
        </section>
    </div>
@endsection
