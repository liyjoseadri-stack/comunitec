@extends('layouts.aplicacion')

@section('titulo', 'Cotizaciones')

@section('contenido')
    <x-encabezado-pagina titulo="Cotizaciones">
        <x-slot:acciones>
            <span class="contador-registros">{{ $cotizaciones->total() }} cotizaciones</span>
        </x-slot:acciones>
    </x-encabezado-pagina>

    <section class="bloque-administrativo" aria-labelledby="titulo-registradas">
        <h2 id="titulo-registradas">
            {{ $estadoFiltrado === 'aceptada' ? 'Cotizaciones listas para venta' : 'Cotizaciones registradas' }}
        </h2>
        <x-tabla-responsive etiqueta="Cotizaciones registradas" adaptable>
            <table class="tabla-registros tabla-adaptable tabla-catalogo">
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
                            <th scope="row" data-etiqueta="Folio"><strong>{{ $cotizacion->folio }}</strong></th>
                            <td data-etiqueta="Cliente">{{ $cotizacion->cliente->nombre }}</td>
                            <td data-etiqueta="Fecha">{{ $cotizacion->creado_en->format('d/m/Y') }}</td>
                            <td data-etiqueta="Estado">
                                <x-insignia-estado :estado="$cotizacion->estado" :etiqueta="$cotizacion->etiquetaEstado()" />
                                @if ($cotizacion->venta)
                                    <small class="detalle-tabla">Convertida en venta</small>
                                @endif
                            </td>
                            <td data-etiqueta="Total">${{ number_format((float) $cotizacion->total, 2) }}</td>
                            <td data-etiqueta="Acción">
                                <x-grupo-acciones-fila>
                                    <x-boton variante="contorno" :href="route('cotizaciones.detalle', $cotizacion)" compacto>
                                        Ver detalle
                                    </x-boton>
                                </x-grupo-acciones-fila>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="tabla-estado-vacio" colspan="6">
                                <x-estado-vacio mensaje="Aún no hay cotizaciones registradas.">
                                    <x-slot:acciones>
                                        @if (auth()->user()->esAdministrador() || auth()->user()->esComercial())
                                            <x-boton variante="contorno" :href="route('cotizaciones.crear')">Crear cotización</x-boton>
                                        @endif
                                    </x-slot:acciones>
                                </x-estado-vacio>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-tabla-responsive>
        {{ $cotizaciones->links() }}
    </section>
@endsection
