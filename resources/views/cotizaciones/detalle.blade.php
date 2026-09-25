@extends('layouts.aplicacion')

@section('titulo', 'Cotización '.$cotizacion->folio)
@section('clase_pagina', 'detalle-cotizacion')

@section('contenido')
            <x-encabezado-pagina
                :titulo="'Cotización '.$cotizacion->folio"
                :descripcion="'Cliente: '.$cotizacion->cliente->nombre"
            >
                <x-slot:acciones>
                    <x-insignia-estado :estado="$cotizacion->estado" :etiqueta="$cotizacion->etiquetaEstado()" />
                    <x-boton variante="contorno" :href="route('cotizaciones.pdf', $cotizacion)">Generar PDF</x-boton>
                </x-slot:acciones>
            </x-encabezado-pagina>
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
                    @if ($cotizacion->vence_en)
                        <div>
                            <dt>Vigencia de la cotización</dt>
                            <dd>{{ $cotizacion->vence_en->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                    @if ($cotizacion->entrega_limite_en)
                        <div>
                            <dt>Fecha límite de entrega</dt>
                            <dd>{{ $cotizacion->entrega_limite_en->format('d/m/Y H:i') }}</dd>
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
            @if ($cotizacion->notas)
                <section class="bloque-administrativo" aria-labelledby="titulo-notas-cotizacion">
                    <h2 id="titulo-notas-cotizacion">Notas y términos comerciales</h2>
                    <p>{{ $cotizacion->notas }}</p>
                </section>
            @endif
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
                                        Tipo
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
                                            {{ $envio->etiquetaTipo() }}
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
            <div class="grupo-acciones acciones-cotizacion">
            @if ($puedeEditar && $cotizacion->estado === 'borrador')
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.enviar', $cotizacion) }}">
                    @csrf
                    <x-boton tipo="submit">Enviar cotización</x-boton>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.cancelar', $cotizacion) }}">
                    @csrf
                    <x-boton variante="peligro" tipo="submit">Cancelar cotización</x-boton>
                </form>
            @elseif ($puedeEditar && $cotizacion->estado === 'pendiente')
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.correo', $cotizacion) }}">
                    @csrf
                    <x-boton tipo="submit">Enviar por correo</x-boton>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.aceptar', $cotizacion) }}">
                    @csrf
                    <x-boton variante="exito" tipo="submit">Aceptar cotización</x-boton>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.rechazar', $cotizacion) }}">
                    @csrf
                    <x-boton variante="peligro" tipo="submit">Rechazar cotización</x-boton>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.cancelar', $cotizacion) }}">
                    @csrf
                    <x-boton variante="secundario" tipo="submit">Cancelar cotización</x-boton>
                </form>
            @endif
            </div>
            @if ($puedeEditar && $cotizacion->estado === 'aceptada')
                <p>
                    Guardar un cambio libera las piezas reservadas y devuelve la cotización a Pendiente para una nueva aceptación.
                </p>
                <section class="bloque-administrativo" aria-labelledby="titulo-continuar-venta">
                    <h2 id="titulo-continuar-venta">Cotización lista para venta</h2>
                    <p>
                        El método de pago y los números de serie se registran en el módulo Ventas.
                    </p>
                    <x-boton :href="route('ventas.crear', $cotizacion)">Continuar en Ventas</x-boton>
                </section>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.cancelar', $cotizacion) }}">
                    @csrf
                    <button type="submit">
                        Cancelar y liberar piezas
                    </button>
                </form>
            @endif
            @if ($puedeEditar && in_array($cotizacion->estado, ['borrador', 'pendiente', 'aceptada'], true))
                <section class="bloque-administrativo seccion-formulario" aria-labelledby="titulo-agregar-concepto">
                    <h2 id="titulo-agregar-concepto">Agregar producto o servicio</h2>
                    <p class="seccion-formulario__introduccion">
                        Selecciona un elemento del inventario o agrega un concepto libre. El precio del catálogo se conserva en esta cotización.
                    </p>
                    <form class="formulario-administrativo formulario-concepto" method="post" action="{{ route('cotizaciones.partidas.guardar', $cotizacion) }}">
                        @csrf
                        <div class="campo-formulario">
                            <label for="tipo-partida">Tipo de concepto</label>
                            <select id="tipo-partida" name="tipo">
                                <option value="producto">Producto</option>
                                <option value="servicio">Servicio</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="campo-formulario" data-catalogo-tipo="producto">
                            <label for="producto-partida">Producto del inventario</label>
                            <select id="producto-partida" name="producto_id">
                                <option value="">Selecciona un producto</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}">
                                        {{ $producto->codigo }} · {{ $producto->nombre }} · {{ $producto->categoria?->nombre ?? 'Sin categoría' }} · ${{ number_format((float) $producto->precio, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="campo-formulario" data-catalogo-tipo="servicio">
                            <label for="servicio-partida">Servicio</label>
                            <select id="servicio-partida" name="servicio_id">
                                <option value="">Selecciona un servicio</option>
                                @foreach ($servicios as $servicio)
                                    <option value="{{ $servicio->id }}">
                                        {{ $servicio->codigo }} · {{ $servicio->nombre }} · {{ $servicio->categoria?->nombre ?? 'Sin categoría' }} · ${{ number_format((float) $servicio->precio, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="campo-formulario campo-descripcion">
                            <label for="descripcion-partida">Descripción</label>
                            <input id="descripcion-partida" name="descripcion" value="{{ old('descripcion') }}" required>
                        </div>
                        <div class="campo-formulario">
                            <label for="cantidad-partida">Cantidad</label>
                            <input id="cantidad-partida" name="cantidad" type="number" step="0.01" min="0.01" required>
                            <small>Los productos requieren cantidades enteras.</small>
                        </div>
                        <div class="campo-formulario">
                            <label for="precio-partida">Precio unitario con IVA</label>
                            <input id="precio-partida" name="precio_unitario" type="number" step="0.01" min="0" value="{{ old('precio_unitario') }}">
                            <small>Solo se captura manualmente para el tipo Otro.</small>
                        </div>
                        <div class="campo-formulario">
                            <label for="descuento-partida">Descuento del concepto (%)</label>
                            <input id="descuento-partida" name="porcentaje_descuento" type="number" min="0" max="100" step="0.01" value="{{ old('porcentaje_descuento', 0) }}" required>
                        </div>
                        <p class="ayuda-formulario">Productos y servicios usan automáticamente el precio vigente del inventario.</p>
                        <div class="acciones-formulario">
                            <button type="submit">Agregar concepto</button>
                        </div>
                    </form>
                </section>
            @endif
            <section class="bloque-administrativo" aria-labelledby="titulo-conceptos-cotizacion">
                <h2 id="titulo-conceptos-cotizacion">Conceptos de la cotización</h2>
                <div class="contenedor-partidas" role="region" aria-label="Productos y servicios de la cotización" tabindex="0">
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
                            @foreach ($cotizacion->partidas as $partida)
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
                                    <td><span class="insignia-tipo">{{ ucfirst($partida->tipo) }}</span></td>
                                    <td>
                                        {{ $partida->articulo?->nombre ?? 'Concepto libre' }}
                                        @if ($partida->articulo?->codigo)
                                            <small class="detalle-tabla">{{ $partida->articulo->codigo }}</small>
                                        @endif
                                    </td>
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
                <p><strong>Total: ${{ $cotizacion->total }}</strong></p>
            </section>

@endsection

@push('scripts')
    <script>
        (() => {
            const tipo = document.querySelector('#tipo-partida');
            const grupos = document.querySelectorAll('[data-catalogo-tipo]');

            if (!tipo || grupos.length === 0) {
                return;
            }

            const actualizar = () => {
                grupos.forEach((grupo) => {
                    const visible = grupo.dataset.catalogoTipo === tipo.value;
                    grupo.hidden = !visible;
                    grupo.querySelector('select').disabled = !visible;
                });
            };

            tipo.addEventListener('change', actualizar);
            actualizar();
        })();
    </script>
@endpush
