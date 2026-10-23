@extends('layouts.aplicacion')

@section('titulo', 'Registrar venta')

@section('contenido')

    <section class="bloque-administrativo" aria-labelledby="titulo-cotizaciones-aceptadas">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <div>
                <h2 id="titulo-cotizaciones-aceptadas">Cotizaciones aceptadas por registrar <span class="contador-titulo">{{ $totalCotizacionesAceptadas }}</span></h2>
                <p>Selecciona una cotización aceptada para confirmar el método de pago y registrar las series entregadas.</p>
            </div>
        </div>
        @if ($totalCotizacionesAceptadas > $cotizacionesAceptadas->count())
            <p class="texto-secundario">
                Se muestran las 10 aceptadas más recientes de {{ $totalCotizacionesAceptadas }}.
                <a href="{{ route('cotizaciones.listado', ['estado' => 'aceptada']) }}">Consultar todas las cotizaciones aceptadas</a>.
            </p>
        @endif
        <x-tabla-responsive etiqueta="Cotizaciones aceptadas pendientes de venta" adaptable>
            <table class="tabla-registros tabla-adaptable">
                <thead>
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Aceptada</th>
                        <th scope="col">Total</th>
                        <th scope="col">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cotizacionesAceptadas as $cotizacion)
                        <tr>
                            <th scope="row" data-etiqueta="Folio">{{ $cotizacion->folio }}</th>
                            <td data-etiqueta="Cliente">{{ $cotizacion->cliente->nombre }}</td>
                            <td data-etiqueta="Aceptada">{{ $cotizacion->aceptada_en?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                            <td data-etiqueta="Total">${{ number_format((float) $cotizacion->total, 2) }}</td>
                            <td data-etiqueta="Acción">
                                <x-grupo-acciones-fila>
                                    <x-boton :href="route('ventas.crear', $cotizacion)" compacto>
                                        Registrar venta
                                    </x-boton>
                                </x-grupo-acciones-fila>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="tabla-estado-vacio">
                                <x-estado-vacio mensaje="No hay cotizaciones aceptadas pendientes de registro.">
                                    <x-slot:acciones>
                                        <x-boton variante="contorno" :href="route('cotizaciones.listado', ['estado' => 'aceptada'])">Consultar cotizaciones aceptadas</x-boton>
                                    </x-slot:acciones>
                                </x-estado-vacio>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-tabla-responsive>
    </section>
@endsection
