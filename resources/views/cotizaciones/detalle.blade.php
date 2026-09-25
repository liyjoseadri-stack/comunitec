@extends('layouts.aplicacion')

@section('titulo', $cotizacion->folio)
@section('clase_pagina', 'detalle-cotizacion')

@section('contenido')
            <h1>
                Cotización {{ $cotizacion->folio }}
            </h1>
            @if ($errors->any())
                <ul role="alert">
                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            @endif
            @if (session('success'))
                <p role="status">
                    {{ session('success') }}
                </p>
            @endif
            @if (session('error'))
                <p role="alert">
                    {{ session('error') }}
                </p>
            @endif
            <p>
                Cliente:
                <strong>
                    {{ $cotizacion->cliente->nombre }}
                </strong>
            </p>
            <p>
                Estado:
                <strong>
                    {{ $cotizacion->etiquetaEstado() }}{{ $cotizacion->venta ? ' · Convertida en venta' : '' }}
                </strong>
            </p>
            @if ($cotizacion->venta)
                <p>
                    Venta relacionada:
                    <a href="{{ route('ventas.detalle', $cotizacion->venta) }}">
                        {{ $cotizacion->venta->folio }}
                    </a>
                </p>
            @endif
            @if ($cotizacion->area_solicitante)
                <p>
                    Área solicitante: {{ $cotizacion->area_solicitante }}
                </p>
            @endif
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
            <p>
                Descuento global: {{ $cotizacion->porcentaje_descuento }}%
            </p>
            @if ($puedeEditar && in_array($cotizacion->estado, ['borrador', 'pendiente', 'aceptada'], true))
                <details>
                    <summary>
                        Editar datos de la cotización
                    </summary>
                    <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.actualizar', $cotizacion) }}">
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
                        <p>
                            Usa 0 para no aplicar descuento, o un porcentaje entre 5 y 10.
                        </p>
                        <button type="submit">
                            Guardar datos de la cotización
                        </button>
                    </form>
                </details>
            @endif
            <p>
                <a href="{{ route('cotizaciones.pdf', $cotizacion) }}">
                    Descargar PDF
                </a>
            </p>
            @if ($cotizacion->enviosCorreo->isNotEmpty())
                <section class="bloque-administrativo" aria-labelledby="titulo-historial-correo">
                    <h2 id="titulo-historial-correo">
                        Historial de correo
                    </h2>
                    <p>
                        Cada registro indica si el servicio de correo aceptó el mensaje. La aceptación técnica no confirma que el cliente lo haya leído.
                    </p>
                    <div class="contenedor-tabla" tabindex="0" aria-label="Historial de envíos por correo">
                        <table class="tabla-registros">
                            <thead>
                                <tr>
                                    <th scope="col">
                                        Fecha
                                    </th>
                                    <th scope="col">
                                        Destinatario
                                    </th>
                                    <th scope="col">
                                        Resultado
                                    </th>
                                    <th scope="col">
                                        Usuario
                                    </th>
                                    <th scope="col">
                                        Detalle
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cotizacion->enviosCorreo as $envio)
                                    <tr>
                                        <td>
                                            {{ $envio->intentado_en->format('d/m/Y H:i') }}
                                        </td>
                                        <td>
                                            {{ $envio->destinatario }}
                                        </td>
                                        <td>
                                            {{ $envio->etiquetaResultado() }}
                                        </td>
                                        <td>
                                            {{ $envio->usuario?->nombre ?? 'Usuario no disponible' }}
                                        </td>
                                        <td>
                                            {{ $envio->mensaje }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
            @if ($puedeEditar && $cotizacion->estado === 'borrador')
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.enviar', $cotizacion) }}">
                    @csrf
                    <button type="submit">
                        Enviar cotización
                    </button>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.cancelar', $cotizacion) }}">
                    @csrf
                    <button type="submit">
                        Cancelar cotización
                    </button>
                </form>
            @elseif ($puedeEditar && $cotizacion->estado === 'pendiente')
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.correo', $cotizacion) }}">
                    @csrf
                    <button type="submit">
                        Enviar por correo al cliente
                    </button>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.aceptar', $cotizacion) }}">
                    @csrf
                    <button type="submit">
                        Aceptar cotización
                    </button>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.rechazar', $cotizacion) }}">
                    @csrf
                    <button type="submit">
                        Rechazar cotización
                    </button>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.cancelar', $cotizacion) }}">
                    @csrf
                    <button type="submit">
                        Cancelar cotización
                    </button>
                </form>
            @endif
            @if ($puedeEditar && $cotizacion->estado === 'aceptada')
                <p>
                    Guardar un cambio libera las piezas reservadas y devuelve la cotización a Pendiente para una nueva aceptación.
                </p>
                <section class="bloque-administrativo" aria-labelledby="titulo-convertir-venta">
                    <h2 id="titulo-convertir-venta">
                        Convertir en venta
                    </h2>
                    <p>
                        Confirma el método de pago y asigna una serie reservada a cada pieza entregada. Esta acción cierra la operación y no vuelve a descontar inventario.
                    </p>
                    <form class="formulario-administrativo formulario-venta" method="post" action="{{ route('ventas.guardar', $cotizacion) }}">
                        @csrf
                        <label for="metodo-pago">Método de pago</label>
                        <select id="metodo-pago" name="metodo_pago" required>
                            <option value="">Selecciona un método</option>
                            <option value="cash" @selected(old('metodo_pago') === 'efectivo')>
                                Efectivo
                            </option>
                            <option value="transfer" @selected(old('metodo_pago') === 'transferencia')>
                                Transferencia
                            </option>
                            <option value="card" @selected(old('metodo_pago') === 'tarjeta')>
                                Tarjeta
                            </option>
                            <option value="otro" @selected(old('metodo_pago') === 'otro')>
                                Otro
                            </option>
                        </select>
                        <label for="detalle-metodo-pago">
                            Especifica el método si elegiste Otro
                        </label>
                        <input
                            id="detalle-metodo-pago"
                            name="detalle_metodo_pago"
                            value="{{ old('detalle_metodo_pago') }}"
                            maxlength="255"
                        >

                        @foreach ($cotizacion->partidas->where('tipo', 'producto') as $partida)
                            @php
                                $seriesDisponibles = $piezasReservadas->where(
                                    'articulo_catalogo_id',
                                    $partida->articulo_catalogo_id
                                );
                            @endphp
                            <fieldset>
                                <legend>
                                    {{ $partida->descripcion }} — {{ (int) $partida->cantidad }} piezas
                                </legend>
                                @for ($indice = 0; $indice < (int) $partida->cantidad; $indice++)
                                    <label for="serie-{{ $partida->id }}-{{ $indice }}">
                                        Serie {{ $indice + 1 }}
                                    </label>
                                    <select
                                        id="serie-{{ $partida->id }}-{{ $indice }}"
                                        name="series[{{ $partida->id }}][]"
                                        required
                                    >
                                        <option value="">Selecciona una serie</option>
                                        @foreach ($seriesDisponibles as $pieza)
                                            <option
                                                value="{{ $pieza->id }}"
                                                @selected(old("series.{$partida->id}.{$indice}") == $pieza->id)
                                            >
                                                {{ $pieza->numero_serie }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endfor
                            </fieldset>
                        @endforeach

                        <p>
                            Cada serie debe seleccionarse una sola vez. Los servicios y conceptos libres no requieren serie.
                        </p>
                        <button type="submit">
                            Registrar venta
                        </button>
                    </form>
                </section>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.cancelar', $cotizacion) }}">
                    @csrf
                    <button type="submit">
                        Cancelar y liberar piezas
                    </button>
                </form>
            @endif
            @if ($puedeEditar && in_array($cotizacion->estado, ['borrador', 'pendiente', 'aceptada'], true))
                <h2>
                    Agregar partida
                </h2>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.partidas.guardar', $cotizacion) }}">
                    @csrf
                    <label for="tipo-partida">Tipo de partida</label>
                    <select id="tipo-partida" name="tipo">
                        <option value="producto">
                            Producto
                        </option>
                        <option value="servicio">
                            Servicio
                        </option>
                        <option value="otro">
                            Otro
                        </option>
                    </select>
                    <label for="articulo-partida">Artículo del catálogo</label>
                    <select id="articulo-partida" name="articulo_catalogo_id">
                        <option value="">
                            Concepto libre
                        </option>
                        @foreach ($articulos as $articulo)
                            <option value="{{ $articulo->id }}">
                                {{ $articulo->nombre }} — {{ $articulo->tipo === 'producto' ? 'Producto' : 'Servicio' }} — ${{ $articulo->precio }}
                            </option>
                        @endforeach
                    </select>
                    <p>Productos y servicios usan el precio vigente del catálogo. La descripción permite precisar lo cotizado.</p>
                    <label for="descripcion-partida">Descripción</label>
                    <input id="descripcion-partida" name="descripcion" value="{{ old('descripcion') }}" required>
                    <label for="cantidad-partida">Cantidad</label>
                    <input id="cantidad-partida" name="cantidad" type="number" step="0.01" min="0.01" required>
                    <p>Los productos requieren cantidades enteras de piezas.</p>
                    <label for="precio-partida">Precio unitario con IVA (solo para Otro)</label>
                    <input id="precio-partida" name="precio_unitario" type="number" step="0.01" min="0" value="{{ old('precio_unitario') }}">
                    <button type="submit">
                        Agregar partida
                    </button>
                </form>
            @endif
            <h2>
                Partidas
            </h2>
            <div class="contenedor-partidas" role="region" aria-label="Partidas de la cotización" tabindex="0">
                <table class="tabla-registros tabla-partidas">
                    <thead>
                        <tr>
                            <th scope="col">
                                Descripción
                            </th>
                            <th scope="col">
                                Cantidad
                            </th>
                            <th scope="col">
                                Precio
                            </th>
                            <th scope="col">
                                Subtotal
                            </th>
                            <th scope="col">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cotizacion->partidas as $partida)
                            @if ($puedeEditar && in_array($cotizacion->estado, ['borrador', 'pendiente', 'aceptada'], true))
                                <tr>
                                    <td colspan="5">
                                        <details>
                                            <summary>Editar partida: {{ $partida->descripcion }}</summary>
                                            <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.partidas.actualizar', [$cotizacion, $partida]) }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="tipo" value="{{ $partida->tipo }}">
                                                <input type="hidden" name="articulo_catalogo_id" value="{{ $partida->articulo_catalogo_id }}">
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
                                                <button type="submit">
                                                    Guardar cambios
                                                </button>
                                            </form>
                                        </details>
                                    </td>
                                </tr>
                            @endif
                            <tr>
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
                                    ${{ $partida->subtotal }}
                                </td>
                                <td>
                                    @if ($puedeEditar && $cotizacion->estado === 'borrador')
                                        <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.partidas.eliminar', [$cotizacion, $partida]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit">
                                                Quitar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p>
                <strong>
                    Total: ${{ $cotizacion->total }}
                </strong>
            </p>

@endsection
