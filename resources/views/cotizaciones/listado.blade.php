@extends('layouts.aplicacion')

@section('titulo', 'Cotizaciones')

@section('contenido')
    <x-encabezado-pagina
        titulo="Cotizaciones"
        descripcion="Crea propuestas comerciales y da seguimiento a su aceptación y vigencia."
    >
        <x-slot:acciones>
            <span class="contador-registros">{{ $cotizaciones->count() }} cotizaciones</span>
        </x-slot:acciones>
    </x-encabezado-pagina>

    @if ($puedeEditar)
        <section
            id="nueva-cotizacion"
            class="bloque-administrativo creador-cotizacion"
            aria-labelledby="titulo-borrador"
            data-creador-cotizacion
            data-url="{{ route('api.cotizaciones.guardar') }}"
        >
            <div class="encabezado-seccion">
                <div>
                    <h2 id="titulo-borrador">Crear cotización</h2>
                    <p>Captura los datos generales y todos los productos o servicios antes de guardar.</p>
                </div>
                <span class="insignia">Borrador</span>
            </div>
            @if ($clientes->isEmpty())
                <div class="alerta alerta--advertencia" role="status">
                    Primero registra un cliente para crear una cotización.
                    <x-boton variante="contorno" :href="route('clientes.listado')" compacto>Ir a Clientes</x-boton>
                </div>
            @endif

            @include('componentes.errores-validacion')

            <div class="alerta alerta--peligro creador-cotizacion__errores" role="alert" hidden></div>

            <form class="formulario-administrativo" data-formulario-cotizacion novalidate>
                @csrf
                <fieldset class="creador-cotizacion__cabecera">
                    <legend>Datos de la cotización</legend>
                    <div class="campo-formulario creador-cotizacion__cliente">
                        <label for="busqueda-cliente">Cliente</label>
                        <div class="campo-autocompletado">
                            <input
                                id="busqueda-cliente"
                                type="search"
                                value="{{ $clientes->firstWhere('id', old('cliente_id'))?->nombre }}"
                                autocomplete="off"
                                placeholder="Buscar cliente por nombre o RFC"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-expanded="false"
                                aria-controls="resultados-clientes"
                                @disabled($clientes->isEmpty())
                            >
                            <input type="hidden" name="cliente_id" value="{{ old('cliente_id') }}" data-cliente-id>
                            <div id="resultados-clientes" class="lista-autocompletado" role="listbox" hidden></div>
                        </div>
                        <small data-cliente-seleccionado>Escribe al menos 2 caracteres y selecciona un cliente.</small>
                    </div>
                    <div class="campo-formulario">
                        <label for="fecha-emision">Fecha de emisión</label>
                        <input id="fecha-emision" type="date" value="{{ now()->toDateString() }}" readonly>
                    </div>
                    <div class="campo-formulario">
                        <label for="fecha-vigencia">Vigencia estimada</label>
                        <input id="fecha-vigencia" type="date" value="{{ $vigenciaEstimada->toDateString() }}" readonly>
                        <small>Se confirma al enviar: 15 días hábiles.</small>
                    </div>
                    <div class="campo-formulario">
                        <label for="folio-informativo">Folio</label>
                        <input id="folio-informativo" value="{{ $folioInformativo }}" readonly>
                        <small>El folio definitivo se asigna al guardar.</small>
                    </div>
                    <div class="campo-formulario">
                        <label for="area">Área solicitante <span class="texto-opcional">(opcional)</span></label>
                        <input id="area" name="area_solicitante" maxlength="255" value="{{ old('area_solicitante') }}">
                    </div>
                    <div class="campo-formulario">
                        <label for="descuento-general">Descuento general (%)</label>
                        <input id="descuento-general" name="porcentaje_descuento" type="number" min="0" max="10" step="0.01" value="{{ old('porcentaje_descuento', 0) }}">
                        <small>Usa 0 o un porcentaje entre 5 y 10. Se aplica después de los descuentos por concepto.</small>
                    </div>
                    <div class="campo-formulario creador-cotizacion__notas">
                        <label for="notas-cotizacion">Notas generales o términos comerciales <span class="texto-opcional">(opcional)</span></label>
                        <textarea id="notas-cotizacion" name="notas" maxlength="3000" rows="3">{{ old('notas') }}</textarea>
                    </div>
                </fieldset>

                <section aria-labelledby="titulo-conceptos-nuevos">
                    <div class="encabezado-seccion">
                        <div>
                            <h3 id="titulo-conceptos-nuevos">Conceptos de la cotización</h3>
                            <p>Busca por nombre, código o categoría. Usa las flechas y Enter para seleccionar.</p>
                        </div>
                        <span class="contador-registros" data-contador-conceptos>0 conceptos</span>
                    </div>

                    <div class="contenedor-tabla creador-cotizacion__tabla" tabindex="0" aria-label="Conceptos de la nueva cotización">
                        <table class="tabla-registros tabla-captura-cotizacion">
                            <thead>
                                <tr>
                                    <th scope="col">Tipo</th>
                                    <th scope="col">Concepto / búsqueda</th>
                                    <th scope="col">Descripción adicional</th>
                                    <th scope="col">Cantidad</th>
                                    <th scope="col">Precio unitario</th>
                                    <th scope="col">Descuento</th>
                                    <th scope="col">Subtotal</th>
                                    <th scope="col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody data-filas-cotizacion></tbody>
                        </table>
                    </div>

                    <button class="boton boton--contorno" type="button" data-agregar-concepto>
                        + Agregar concepto
                    </button>
                </section>

                <div class="creador-cotizacion__cierre">
                    <aside class="creador-cotizacion__atajos" aria-label="Atajos de teclado">
                        <strong>Captura rápida</strong>
                        <span><kbd>↑</kbd><kbd>↓</kbd> recorrer resultados</span>
                        <span><kbd>Enter</kbd> seleccionar o agregar otra fila</span>
                        <span><kbd>Esc</kbd> cerrar resultados</span>
                    </aside>
                    <dl class="totales-cotizacion" aria-live="polite">
                        <div><dt>Subtotal antes de descuentos</dt><dd data-total-bruto>$0.00</dd></div>
                        <div><dt>Descuento acumulado</dt><dd data-total-descuento>−$0.00</dd></div>
                        <div><dt>Subtotal sin IVA</dt><dd data-total-subtotal>$0.00</dd></div>
                        <div><dt>IVA incluido (16%)</dt><dd data-total-iva>$0.00</dd></div>
                        <div class="totales-cotizacion__final"><dt>Total</dt><dd data-total-final>$0.00</dd></div>
                    </dl>
                </div>

                <div class="acciones-formulario">
                    <x-boton tipo="submit" :disabled="$clientes->isEmpty()" data-guardar-cotizacion>
                        Guardar cotización
                    </x-boton>
                </div>
            </form>

            <script type="application/json" data-datos-cotizacion>
                @json($datosCreador)
            </script>
        </section>
    @endif

    <section class="bloque-administrativo" aria-labelledby="titulo-registradas">
        <h2 id="titulo-registradas">
            {{ $estadoFiltrado === 'aceptada' ? 'Cotizaciones listas para venta' : 'Cotizaciones registradas' }}
        </h2>
        <div class="contenedor-tabla" tabindex="0" aria-label="Cotizaciones registradas">
            <table class="tabla-registros tabla-catalogo">
                <thead>
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Total</th>
                        <th scope="col">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cotizaciones as $cotizacion)
                        <tr>
                            <td><strong>{{ $cotizacion->folio }}</strong></td>
                            <td>{{ $cotizacion->cliente->nombre }}</td>
                            <td>{{ $cotizacion->creado_en->format('d/m/Y') }}</td>
                            <td>
                                <x-insignia-estado :estado="$cotizacion->estado" :etiqueta="$cotizacion->etiquetaEstado()" />
                                @if ($cotizacion->venta)
                                    <small class="detalle-tabla">Convertida en venta</small>
                                @endif
                            </td>
                            <td>${{ number_format((float) $cotizacion->total, 2) }}</td>
                            <td>
                                <x-boton variante="contorno" :href="route('cotizaciones.detalle', $cotizacion)" compacto>
                                    Ver detalle
                                </x-boton>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="estado-vacio" colspan="6">Aún no hay cotizaciones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/creador-cotizacion.js') }}" defer></script>
@endpush
