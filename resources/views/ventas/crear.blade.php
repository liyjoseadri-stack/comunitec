@extends('layouts.aplicacion')

@section('titulo', 'Registrar venta')

@section('contenido')
    <header class="encabezado-pagina">
        <p>Convierte la cotización {{ $cotizacion->folio }} sin volver a capturar sus datos.</p>
        <x-insignia-estado estado="aceptada" etiqueta="Cotización aceptada" />
    </header>

    @include('componentes.errores-validacion')

    <section class="bloque-administrativo" aria-labelledby="titulo-resumen-origen">
        <h2 id="titulo-resumen-origen">Información proveniente de la cotización</h2>
        <dl class="datos-operacion">
            <div><dt>Cliente</dt><dd>{{ $cotizacion->cliente->nombre }}</dd></div>
            <div><dt>Folio de cotización</dt><dd>{{ $cotizacion->folio }}</dd></div>
            <div><dt>Descuento global</dt><dd>{{ number_format((float) $cotizacion->porcentaje_descuento, 2) }}%</dd></div>
            <div><dt>Total</dt><dd>${{ number_format((float) $cotizacion->total, 2) }}</dd></div>
        </dl>

        <div class="contenedor-tabla" tabindex="0">
            <table class="tabla-registros">
                <thead>
                    <tr><th>Tipo</th><th>Concepto</th><th>Descripción</th><th>Cantidad</th><th>Precio unitario</th><th>Descuento</th><th>Importe</th></tr>
                </thead>
                <tbody>
                    @foreach ($cotizacion->partidas as $partida)
                        <tr>
                            <td><span class="insignia-tipo">{{ ucfirst($partida->tipo) }}</span></td>
                            <td>{{ $partida->articulo?->nombre ?? 'Concepto libre' }}</td>
                            <td>{{ $partida->descripcion }}</td>
                            <td>{{ number_format((float) $partida->cantidad, 2) }}</td>
                            <td>${{ number_format((float) $partida->precio_unitario, 2) }}</td>
                            <td>{{ (float) $cotizacion->porcentaje_descuento > 0 ? number_format((float) $cotizacion->porcentaje_descuento, 2).'% global' : 'Sin descuento' }}</td>
                            <td>${{ number_format((float) $partida->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="bloque-administrativo" aria-labelledby="titulo-datos-cierre">
        <h2 id="titulo-datos-cierre">Datos para registrar la venta</h2>
        <form class="formulario-administrativo formulario-venta" method="post" action="{{ route('ventas.guardar', $cotizacion) }}">
            @csrf
            <div class="campo-formulario">
                <label for="metodo-pago">Método de pago</label>
                <select id="metodo-pago" name="metodo_pago" required>
                    <option value="">Selecciona un método</option>
                    @foreach (['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta', 'otro' => 'Otro'] as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(old('metodo_pago') === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="campo-formulario">
                <label for="detalle-metodo-pago">Descripción si elegiste Otro</label>
                <input id="detalle-metodo-pago" name="detalle_metodo_pago" value="{{ old('detalle_metodo_pago') }}" maxlength="255">
            </div>

            @foreach ($cotizacion->partidas->filter(fn ($partida) => $partida->tipo === 'producto' && $partida->articulo?->requiere_numero_serie) as $partida)
                <fieldset class="campo-formulario campo-formulario--ancho">
                    <legend>{{ $partida->articulo?->nombre ?? $partida->descripcion }} — {{ (int) $partida->cantidad }} equipos</legend>
                    <p>{{ $partida->descripcion }}</p>
                    @for ($indice = 0; $indice < (int) $partida->cantidad; $indice++)
                        <label for="serie-{{ $partida->id }}-{{ $indice }}">Equipo {{ $indice + 1 }} · Número de serie</label>
                        <input id="serie-{{ $partida->id }}-{{ $indice }}" name="series[{{ $partida->id }}][]" value="{{ old("series.{$partida->id}.{$indice}") }}" maxlength="255" autocomplete="off" required>
                    @endfor
                </fieldset>
            @endforeach

            <p class="ayuda-campo">Los servicios y productos configurados sin número de serie no solicitan estos campos.</p>
            <div class="grupo-acciones">
                <x-boton tipo="submit">Registrar venta</x-boton>
                <x-boton variante="contorno" :href="route('ventas.listado')">Cancelar</x-boton>
            </div>
        </form>
    </section>
@endsection
