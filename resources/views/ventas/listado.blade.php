@extends('layouts.aplicacion')

@section('titulo', 'Ventas')

@section('contenido')
    <x-encabezado-pagina titulo="Ventas">
        <x-slot:acciones>
            <span class="contador-registros">
                {{ $ventas->total() }} {{ $ventas->total() === 1 ? 'venta' : 'ventas' }}
            </span>
            @if ((auth()->user()->esAdministrador() || auth()->user()->esComercial()) && $cotizacionesAceptadas->isNotEmpty())
                <x-boton :href="route('ventas.listado').'#cotizaciones-aceptadas'">Registrar venta</x-boton>
            @endif
        </x-slot:acciones>
    </x-encabezado-pagina>

            @if (auth()->user()->esAdministrador() || auth()->user()->esComercial())
                <section id="cotizaciones-aceptadas" class="bloque-administrativo" aria-labelledby="titulo-cotizaciones-aceptadas">
                    <h2 id="titulo-cotizaciones-aceptadas">Cotizaciones aceptadas por registrar</h2>
                    <p>Selecciona una cotización aceptada para confirmar el método de pago y registrar las series entregadas.</p>
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
            @endif

            <section class="bloque-administrativo" aria-labelledby="titulo-ventas">
                <div class="encabezado-seccion">
                    <div>
                        <h2 id="titulo-ventas">Historial de ventas</h2>
                        <p>Consulta los datos conservados al momento de registrar cada venta.</p>
                    </div>
                </div>

                <form class="formulario-filtros" method="GET" action="{{ route('ventas.listado') }}">
                    <x-campo-formulario
                        id="buscar-ventas"
                        nombre="buscar"
                        etiqueta="Buscar venta"
                        :valor="$filtros['buscar']"
                        placeholder="Folio, cliente, RFC, correo o cotización"
                    />
                    <x-campo-formulario id="metodo-pago-ventas" nombre="metodo_pago" etiqueta="Método de pago" tipo="select">
                        <option value="">Todos</option>
                        @foreach ([
                            \App\Models\Venta::METODO_EFECTIVO => 'Efectivo',
                            \App\Models\Venta::METODO_TRANSFERENCIA => 'Transferencia',
                            \App\Models\Venta::METODO_TARJETA => 'Tarjeta',
                            \App\Models\Venta::METODO_OTRO => 'Otro',
                        ] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($filtros['metodo_pago'] === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </x-campo-formulario>
                    <x-grupo-acciones-fila>
                        <x-boton tipo="submit" variante="secundario">Aplicar filtros</x-boton>
                        @if ($filtros['buscar'] !== '' || $filtros['metodo_pago'] !== '')
                            <x-boton variante="contorno" :href="route('ventas.listado')">Limpiar filtros</x-boton>
                        @endif
                    </x-grupo-acciones-fila>
                </form>

                <x-tabla-responsive etiqueta="Ventas registradas" adaptable :vacia="$ventas->isEmpty()">
                    <table class="tabla-registros tabla-adaptable">
                        <thead>
                            <tr>
                                <th scope="col">Folio</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Cotización</th>
                                <th scope="col">Método de pago</th>
                                <th scope="col">Total</th>
                                <th scope="col">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ventas as $venta)
                                <tr>
                                    <th scope="row" data-etiqueta="Folio"><strong>{{ $venta->folio }}</strong></th>
                                    <td data-etiqueta="Fecha">{{ $venta->vendida_en->format('d/m/Y H:i') }}</td>
                                    <td data-etiqueta="Cliente">{{ $venta->nombreClienteMostrado() }}</td>
                                    <td data-etiqueta="Cotización">
                                        <x-boton variante="contorno" :href="route('cotizaciones.detalle', $venta->cotizacion)" compacto>
                                            {{ $venta->cotizacion->folio }}
                                        </x-boton>
                                    </td>
                                    <td data-etiqueta="Método de pago">{{ $venta->etiquetaMetodoPago() }}</td>
                                    <td data-etiqueta="Total">${{ number_format((float) $venta->total, 2) }}</td>
                                    <td data-etiqueta="Acción">
                                        <x-grupo-acciones-fila>
                                            <x-boton variante="contorno" :href="route('ventas.detalle', $venta)" compacto>
                                                Ver detalle
                                            </x-boton>
                                        </x-grupo-acciones-fila>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="tabla-estado-vacio" colspan="7">
                                        <x-estado-vacio :mensaje="($filtros['buscar'] !== '' || $filtros['metodo_pago'] !== '') ? 'No hay ventas que coincidan con los filtros seleccionados.' : 'Aún no hay ventas registradas. Primero debe aceptarse y convertirse una cotización.'">
                                            <x-slot:acciones>
                                                @if ($filtros['buscar'] !== '' || $filtros['metodo_pago'] !== '')
                                                    <x-boton variante="contorno" :href="route('ventas.listado')">Limpiar filtros</x-boton>
                                                @else
                                                    <x-boton variante="contorno" :href="route('cotizaciones.listado', ['estado' => 'aceptada'])">Consultar cotizaciones aceptadas</x-boton>
                                                @endif
                                            </x-slot:acciones>
                                        </x-estado-vacio>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-tabla-responsive>
                @include('componentes.paginacion', ['paginador' => $ventas])
            </section>

@endsection
