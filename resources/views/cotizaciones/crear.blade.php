@extends('layouts.aplicacion')

@section('titulo', 'Nueva cotización')

@section('contexto_carga', 'formulario')
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
                    <h2 id="titulo-borrador">Borrador de cotización</h2>
                    <p>Captura los datos generales y todos los productos o servicios antes de guardar.</p>
                </div>
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
                    <x-campo-formulario id="descuento-general" nombre="porcentaje_descuento" etiqueta="Descuento general (%)" tipo="number" :valor="old('porcentaje_descuento', 0)" ayuda="Usa 0 o un número entero entre 5 y 10. Se aplica después de los descuentos por concepto." min="0" max="10" step="1" inputmode="numeric" data-entero />
                    <x-campo-formulario id="notas-cotizacion" nombre="notas" etiqueta="Notas generales o términos comerciales (opcional)" tipo="textarea" contenedor="campo-formulario creador-cotizacion__notas" :valor="old('notas')" maxlength="3000" rows="3" />
                </fieldset>

                <section class="creador-cotizacion__seccion creador-cotizacion__seccion--conceptos" aria-labelledby="titulo-conceptos-nuevos">
                    <div class="encabezado-seccion">
                        <div>
                            <h3 id="titulo-conceptos-nuevos">Productos y servicios <span class="contador-titulo" data-contador-conceptos>0</span></h3>
                            <p>Agrega los productos o servicios que formarán parte de esta cotización.</p>
                        </div>
                    </div>

                    <div class="estado-vacio creador-cotizacion__vacio" data-conceptos-vacio>
                        <strong>Aún no agregas productos o servicios</strong>
                        <p>Cuando necesites incluir algo del inventario, agrégalo aquí para capturar cantidad, descuento y precio.</p>
                        <button class="boton boton--contorno" type="button" data-agregar-concepto>
                            + Agregar producto o servicio
                        </button>
                    </div>

                    <div data-tabla-conceptos hidden>
                        <x-tabla-responsive etiqueta="Productos y servicios de la nueva cotización" adaptable class="creador-cotizacion__tabla">
                            <table class="tabla-registros tabla-captura-cotizacion">
                                <thead>
                                    <tr>
                                        <th scope="col">Tipo</th>
                                        <th scope="col">Concepto / búsqueda</th>
                                        <th scope="col">Descripción del catálogo</th>
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

                        <div class="creador-cotizacion__acciones-conceptos">
                            <button class="boton boton--contorno" type="button" data-agregar-concepto-otro>
                                + Agregar otro producto o servicio
                            </button>
                        </div>
                    </div>
                </section>

                <section class="creador-cotizacion__seccion creador-cotizacion__seccion--pcs creador-cotizacion__pcs" aria-labelledby="titulo-pcs">
                    <div class="encabezado-seccion encabezado-seccion--acciones">
                        <div>
                            <h3 id="titulo-pcs">PC armadas <span class="contador-titulo" data-contador-pcs>0</span></h3>
                            <p>Agrega una plantilla o personaliza una PC. Al aceptar la cotización se descontarán las existencias de sus componentes.</p>
                        </div>
                        <div class="grupo-acciones">
                            <button class="boton boton--secundario" type="button" data-abrir-plantillas-pc>Agregar PC de plantilla</button>
                            <button class="boton boton--contorno" type="button" data-abrir-personalizar-pc>Personalizar PC</button>
                        </div>
                    </div>
                    <div class="creador-cotizacion__pcs-lista" data-pcs-resumen aria-live="polite">
                        <p class="estado-vacio estado-vacio--compacto" data-pcs-vacio>Aún no agregas una PC armada.</p>
                    </div>
                </section>

                <dialog
                    class="dialogo-pc-armada"
                    data-dialogo-pc-armada
                    aria-labelledby="titulo-dialogo-pc-armada"
                >
                    <div class="dialogo-pc-armada__superficie">
                        <div class="dialogo-pc-armada__encabezado">
                            <div>
                                <p class="ceja-seccion">Cotización</p>
                                <h2 id="titulo-dialogo-pc-armada" data-dialogo-titulo>Agregar PC armada</h2>
                                <p class="texto-ayuda" data-dialogo-instruccion>Selecciona una opción para continuar.</p>
                            </div>
                            <button class="boton boton--contorno boton--compacto" type="button" data-cerrar-dialogo-pc>
                                Cerrar
                            </button>
                        </div>

                        <div data-dialogo-plantillas-pc hidden>
                            <div class="pc-plantillas-opciones" data-plantillas-pc-opciones></div>
                            <p class="estado-vacio estado-vacio--compacto" data-plantillas-pc-vacio hidden>
                                No hay plantillas activas. Puedes personalizar una PC para esta cotización.
                            </p>
                        </div>

                        <div data-dialogo-editor-pc hidden>
                            <div data-editor-pc></div>
                            <div class="acciones-formulario dialogo-pc-armada__acciones">
                                <button class="boton boton--contorno" type="button" data-cancelar-edicion-pc>Cancelar</button>
                                <button class="boton boton--secundario" type="button" data-guardar-edicion-pc>Guardar PC</button>
                            </div>
                        </div>
                    </div>
                </dialog>

                <section class="creador-cotizacion__seccion creador-cotizacion__seccion--resumen" aria-labelledby="titulo-resumen-cotizacion">
                    <div class="encabezado-seccion">
                        <div>
                            <h3 id="titulo-resumen-cotizacion">Resumen de la cotización</h3>
                            <p>Revisa el importe final antes de guardar el borrador.</p>
                        </div>
                    </div>
                    <div class="creador-cotizacion__cierre">
                        <dl class="totales-cotizacion" aria-live="polite">
                            <div><dt>Subtotal antes de descuentos</dt><dd data-total-bruto>$0.00</dd></div>
                            <div><dt>Descuento acumulado</dt><dd data-total-descuento>−$0.00</dd></div>
                            <div><dt>Subtotal sin IVA</dt><dd data-total-subtotal>$0.00</dd></div>
                            <div><dt>IVA incluido (16%)</dt><dd data-total-iva>$0.00</dd></div>
                            <div class="totales-cotizacion__final"><dt>Total</dt><dd data-total-final>$0.00</dd></div>
                        </dl>
                    </div>
                </section>

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
    <script src="{{ asset('js/creador-cotizacion.js') }}?v={{ filemtime(public_path('js/creador-cotizacion.js')) }}" defer></script>
@endpush
