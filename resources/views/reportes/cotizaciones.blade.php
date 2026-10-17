@extends('layouts.aplicacion')

@section('titulo', 'Reporte de cotizaciones')

@section('contenido')
            <x-reportes.filtros
                :accion="route('reportes.cotizaciones')"
                :filtros="$filtros"
                :clientes="$clientes"
                :responsables="$responsables"
                titulo-id="titulo-filtros-cotizaciones"
            >
                <x-campo-formulario id="estado" nombre="estado" etiqueta="Estado" tipo="select" contenedor="campo-filtro">
                            <option value="">Todos</option>
                            @foreach ([
                                'borrador' => 'Borrador',
                                'pendiente' => 'Pendiente',
                                'aceptada' => 'Aceptada',
                                'rechazada' => 'Rechazada',
                                'cancelada' => 'Cancelada',
                                'vencida' => 'Vencida',
                                'venta' => 'Venta',
                            ] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(($filtros['estado'] ?? null) === $valor)>
                                    {{ $etiqueta }}
                                </option>
                            @endforeach

                </x-campo-formulario>
            </x-reportes.filtros>

            <section class="bloque-administrativo" aria-labelledby="titulo-resultados-cotizaciones">
                <x-reportes.resumen-resultados
                    :cantidad="$cantidadResultados"
                    etiqueta-total="Total cotizado"
                    titulo-id="titulo-resultados-cotizaciones"
                    :total="$totalImporte"
                />
                <x-tabla-responsive etiqueta="Resultados del reporte de cotizaciones" :vacia="$cotizaciones->isEmpty()" adaptable>
                    <table class="tabla-registros tabla-adaptable">
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Folio</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Responsable</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Total</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cotizaciones as $cotizacion)
                                <tr>
                                    <td data-etiqueta="Fecha">{{ $cotizacion->creado_en->format('d/m/Y') }}</td>
                                    <th scope="row" data-etiqueta="Folio">{{ $cotizacion->folio }}</th>
                                    <td data-etiqueta="Cliente">{{ $cotizacion->cliente->nombre }}</td>
                                    <td data-etiqueta="Responsable">{{ $cotizacion->responsable->nombre }}</td>
                                    <td data-etiqueta="Estado">
                                        <x-insignia-estado :estado="$cotizacion->estado" :etiqueta="$cotizacion->etiquetaEstado()" />
                                    </td>
                                    <td data-etiqueta="Total">${{ number_format((float) $cotizacion->total, 2) }}</td>
                                    <td data-etiqueta="Acciones">
                                        <x-grupo-acciones-fila>
                                            <x-boton variante="contorno" :href="route('cotizaciones.detalle', $cotizacion)" compacto>
                                                Ver detalle
                                            </x-boton>
                                        </x-grupo-acciones-fila>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="tabla-estado-vacio" colspan="7">
                                        <x-estado-vacio mensaje="No hay cotizaciones con estos filtros.">
                                            <x-slot:acciones>
                                                <x-boton variante="contorno" :href="route('reportes.cotizaciones')">Limpiar filtros</x-boton>
                                            </x-slot:acciones>
                                        </x-estado-vacio>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-tabla-responsive>
                @include('componentes.paginacion', ['paginador' => $cotizaciones])
            </section>

@endsection
