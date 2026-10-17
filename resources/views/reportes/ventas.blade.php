@extends('layouts.aplicacion')

@section('titulo', 'Reporte de ventas')

@section('contenido')
            <x-reportes.filtros
                :accion="route('reportes.ventas')"
                :filtros="$filtros"
                :clientes="$clientes"
                :responsables="$responsables"
                titulo-id="titulo-filtros-ventas"
            >
                <x-campo-formulario id="metodo-pago" nombre="metodo_pago" etiqueta="Método de pago" tipo="select" contenedor="campo-filtro">
                            <option value="">Todos</option>
                            @foreach ([
                                'efectivo' => 'Efectivo',
                                'transferencia' => 'Transferencia',
                                'tarjeta' => 'Tarjeta',
                                'otro' => 'Otro',
                            ] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(($filtros['metodo_pago'] ?? null) === $valor)>
                                    {{ $etiqueta }}
                                </option>
                            @endforeach

                </x-campo-formulario>
            </x-reportes.filtros>

            <section class="bloque-administrativo" aria-labelledby="titulo-resultados-ventas">
                <x-reportes.resumen-resultados
                    :cantidad="$cantidadResultados"
                    etiqueta-total="Total vendido"
                    titulo-id="titulo-resultados-ventas"
                    :total="$totalImporte"
                />
                <x-tabla-responsive etiqueta="Resultados del reporte de ventas" :vacia="$ventas->isEmpty()" adaptable>
                    <table class="tabla-registros tabla-adaptable">
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Folio</th>
                                <th scope="col">Cotización</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Responsable</th>
                                <th scope="col">Método de pago</th>
                                <th scope="col">Total</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ventas as $venta)
                                <tr>
                                    <td data-etiqueta="Fecha">{{ $venta->vendida_en->format('d/m/Y') }}</td>
                                    <th scope="row" data-etiqueta="Folio">{{ $venta->folio }}</th>
                                    <td data-etiqueta="Cotización">
                                        <a href="{{ route('cotizaciones.detalle', $venta->cotizacion) }}">
                                            {{ $venta->cotizacion->folio }}
                                        </a>
                                    </td>
                                    <td data-etiqueta="Cliente">{{ $venta->nombreClienteMostrado() }}</td>
                                    <td data-etiqueta="Responsable">{{ $venta->nombreResponsableMostrado() }}</td>
                                    <td data-etiqueta="Método de pago">{{ $venta->etiquetaMetodoPago() }}</td>
                                    <td data-etiqueta="Total">${{ number_format((float) $venta->total, 2) }}</td>
                                    <td data-etiqueta="Acciones">
                                        <x-grupo-acciones-fila>
                                            <x-boton variante="contorno" :href="route('ventas.detalle', $venta)" compacto>
                                                Ver detalle
                                            </x-boton>
                                        </x-grupo-acciones-fila>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="tabla-estado-vacio" colspan="8">
                                        <x-estado-vacio mensaje="No hay ventas con estos filtros.">
                                            <x-slot:acciones>
                                                <x-boton variante="contorno" :href="route('reportes.ventas')">Limpiar filtros</x-boton>
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
