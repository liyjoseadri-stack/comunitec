@extends('layouts.aplicacion')

@section('titulo', 'Nueva cotización')

@section('contenido')
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
                                aria-describedby="ayuda-cliente{{ $errors->has('cliente_id') ? ' busqueda-cliente-error' : '' }}"
                                @if ($errors->has('cliente_id')) aria-invalid="true" @endif
                                @disabled($clientes->isEmpty())
                            >
                            <input type="hidden" name="cliente_id" value="{{ old('cliente_id') }}" data-cliente-id>
                            <div id="resultados-clientes" class="lista-autocompletado" role="listbox" hidden></div>
                        </div>
                        <small id="ayuda-cliente" data-cliente-seleccionado>Escribe al menos 2 caracteres y selecciona un cliente.</small>
                        @error('cliente_id')
                            <p id="busqueda-cliente-error" class="error-campo">{{ $message }}</p>
                        @enderror
                    </div>
                    <x-campo-formulario id="fecha-emision" etiqueta="Fecha de emisión" tipo="date" :valor="now()->toDateString()" readonly />
                    <x-campo-formulario id="fecha-vigencia" etiqueta="Vigencia estimada" tipo="date" :valor="$vigenciaEstimada->toDateString()" ayuda="Se confirma al enviar: 15 días hábiles." readonly />
                    <x-campo-formulario id="folio-informativo" etiqueta="Folio" :valor="$folioInformativo" ayuda="El folio definitivo se asigna al guardar." readonly />
                    <x-campo-formulario id="area" nombre="area_solicitante" etiqueta="Área solicitante (opcional)" :valor="old('area_solicitante')" maxlength="255" />
                    <x-campo-formulario id="descuento-general" nombre="porcentaje_descuento" etiqueta="Descuento general (%)" tipo="number" :valor="old('porcentaje_descuento', 0)" ayuda="Usa 0 o un porcentaje entre 5 y 10. Se aplica después de los descuentos por concepto." min="0" max="10" step="0.01" />
                    <x-campo-formulario id="notas-cotizacion" nombre="notas" etiqueta="Notas generales o términos comerciales (opcional)" tipo="textarea" contenedor="campo-formulario creador-cotizacion__notas" :valor="old('notas')" maxlength="3000" rows="3" />
                </fieldset>

                <section aria-labelledby="titulo-conceptos-nuevos">
                    <div class="encabezado-seccion">
                        <div>
                            <h3 id="titulo-conceptos-nuevos">Conceptos de la cotización</h3>
                            <p>Busca por nombre, código o categoría. Usa las flechas y Enter para seleccionar.</p>
                        </div>
                        <span class="contador-registros" data-contador-conceptos>0 conceptos</span>
                    </div>

                    <x-tabla-responsive etiqueta="Conceptos de la nueva cotización" adaptable class="creador-cotizacion__tabla">
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
                    </x-tabla-responsive>

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

@endsection

@push('scripts')
    <script src="{{ asset('js/creador-cotizacion.js') }}" defer></script>
@endpush
