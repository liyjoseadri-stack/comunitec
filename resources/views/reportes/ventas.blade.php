@extends('layouts.aplicacion')

@section('titulo', 'Reporte de ventas')

@section('contenido')
            <header class="encabezado-pagina">
                <div>
                    <h1>Reporte de ventas</h1>
                    <p>Consulta las ventas cerradas y sus importes históricos.</p>
                </div>
                <span class="contador-registros">
                    {{ $cantidadResultados }} {{ $cantidadResultados === 1 ? 'resultado' : 'resultados' }}
                </span>
            </header>

            @include('componentes.errores-validacion')

            <nav class="pestanas-reportes" aria-label="Tipos de reporte">
                <a href="{{ route('reportes.cotizaciones') }}">Cotizaciones</a>
                <a href="{{ route('reportes.ventas') }}" aria-current="page">Ventas</a>
            </nav>

            <section class="bloque-administrativo" aria-labelledby="titulo-filtros-ventas">
                <h2 id="titulo-filtros-ventas">Filtros</h2>
                <form class="formulario-filtros" method="GET" action="{{ route('reportes.ventas') }}">
                    <div class="campo-filtro">
                        <label for="desde">Desde</label>
                        <input id="desde" name="desde" type="date" value="{{ $filtros['desde'] }}">
                    </div>
                    <div class="campo-filtro">
                        <label for="hasta">Hasta</label>
                        <input id="hasta" name="hasta" type="date" value="{{ $filtros['hasta'] }}">
                    </div>
                    <div class="campo-filtro">
                        <label for="cliente">Cliente</label>
                        <select id="cliente" name="cliente">
                            <option value="">Todos</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected(($filtros['cliente'] ?? null) == $cliente->id)>
                                    {{ $cliente->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="campo-filtro">
                        <label for="responsable">Responsable</label>
                        <select id="responsable" name="responsable">
                            <option value="">Todos</option>
                            @foreach ($responsables as $responsable)
                                <option value="{{ $responsable->id }}" @selected(($filtros['responsable'] ?? null) == $responsable->id)>
                                    {{ $responsable->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="campo-filtro">
                        <label for="metodo-pago">Método de pago</label>
                        <select id="metodo-pago" name="metodo_pago">
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
                        </select>
                    </div>

                    <button type="submit">Aplicar filtros</button>
                </form>
            </section>

            <section class="bloque-administrativo" aria-labelledby="titulo-resultados-ventas">
                <div class="encabezado-seccion">
                    <h2 id="titulo-resultados-ventas">Resultados</h2>
                    <strong>Total vendido: ${{ number_format($totalImporte, 2) }}</strong>
                </div>
                <div class="contenedor-tabla" tabindex="0" aria-label="Resultados del reporte de ventas">
                    <table class="tabla-registros">
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Folio</th>
                                <th scope="col">Cotización</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Responsable</th>
                                <th scope="col">Método de pago</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ventas as $venta)
                                <tr>
                                    <td>{{ $venta->vendida_en->format('d/m/Y') }}</td>
                                    <td>
                                        <x-boton variante="contorno" :href="route('ventas.detalle', $venta)" compacto>
                                            Ver detalle
                                        </x-boton>
                                        <small class="detalle-tabla">{{ $venta->folio }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('cotizaciones.detalle', $venta->cotizacion) }}">
                                            {{ $venta->cotizacion->folio }}
                                        </a>
                                    </td>
                                    <td>{{ $venta->nombreClienteMostrado() }}</td>
                                    <td>{{ $venta->nombreResponsableMostrado() }}</td>
                                    <td>{{ $venta->etiquetaMetodoPago() }}</td>
                                    <td>${{ number_format((float) $venta->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="estado-vacio" colspan="7">No hay ventas con estos filtros.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('componentes.paginacion', ['paginador' => $ventas])
            </section>

@endsection
