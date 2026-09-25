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
        <section class="bloque-administrativo" aria-labelledby="titulo-borrador">
            <h2 id="titulo-borrador">Nueva cotización</h2>
            @if ($clientes->isEmpty())
                <div class="alerta alerta--advertencia" role="status">
                    Primero registra un cliente para crear una cotización.
                    <x-boton variante="contorno" :href="route('clientes.listado')" compacto>Ir a Clientes</x-boton>
                </div>
            @endif

            @include('componentes.errores-validacion')

            <form class="formulario-administrativo formulario-dos-columnas" method="post" action="{{ route('cotizaciones.guardar') }}">
                @csrf
                <div class="campo-formulario campo-formulario--ancho">
                    <label for="cliente">Cliente</label>
                    <select id="cliente" name="cliente_id" required>
                        <option value="">Selecciona un cliente</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="campo-formulario">
                    <label for="area">Área solicitante <span class="texto-opcional">(opcional)</span></label>
                    <input id="area" name="area_solicitante" maxlength="255" value="{{ old('area_solicitante') }}">
                </div>
                <div class="campo-formulario">
                    <label for="descuento">Descuento (%)</label>
                    <input id="descuento" name="porcentaje_descuento" type="number" min="0" max="10" step="0.01" value="{{ old('porcentaje_descuento', 0) }}" aria-describedby="ayuda-descuento">
                    <small id="ayuda-descuento">Usa 0 sin descuento o un porcentaje entre 5 y 10.</small>
                </div>
                <div class="acciones-formulario campo-formulario--ancho">
                    <x-boton tipo="submit" :disabled="$clientes->isEmpty()">Crear borrador</x-boton>
                </div>
            </form>
        </section>
    @endif

    <section class="bloque-administrativo" aria-labelledby="titulo-registradas">
        <h2 id="titulo-registradas">Cotizaciones registradas</h2>
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
