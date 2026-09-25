@extends('layouts.aplicacion')

@section('titulo', 'Reporte de cotizaciones')

@section('contenido')
            <header class="encabezado-pagina">
                <div>
                    <h1>Reporte de cotizaciones</h1>
                    <p>Consulta las cotizaciones que forman los indicadores comerciales.</p>
                </div>
                <span class="contador-registros">
                    {{ $cantidadResultados }} {{ $cantidadResultados === 1 ? 'resultado' : 'resultados' }}
                </span>
            </header>

            @include('componentes.errores-validacion')

            <nav class="pestanas-reportes" aria-label="Tipos de reporte">
                <a href="{{ route('reportes.cotizaciones') }}" aria-current="page">Cotizaciones</a>
                <a href="{{ route('reportes.ventas') }}">Ventas</a>
            </nav>

            <section class="bloque-administrativo" aria-labelledby="titulo-filtros-cotizaciones">
                <h2 id="titulo-filtros-cotizaciones">Filtros</h2>
                <form class="formulario-filtros" method="GET" action="{{ route('reportes.cotizaciones') }}">
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
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            <option value="">Todos</option>
                            @foreach ([
                                'borrador' => 'Borrador',
                                'pendiente' => 'Pendiente',
                                'aceptada' => 'Aceptada',
                                'rechazada' => 'Rechazada',
                                'cancelada' => 'Cancelada',
                                'vencida' => 'Vencida',
                            ] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(($filtros['estado'] ?? null) === $valor)>
                                    {{ $etiqueta }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit">Aplicar filtros</button>
                </form>
            </section>

            <section class="bloque-administrativo" aria-labelledby="titulo-resultados-cotizaciones">
                <div class="encabezado-seccion">
                    <h2 id="titulo-resultados-cotizaciones">Resultados</h2>
                    <strong>Total cotizado: ${{ number_format($totalImporte, 2) }}</strong>
                </div>
                <div class="contenedor-tabla" tabindex="0" aria-label="Resultados del reporte de cotizaciones">
                    <table class="tabla-registros">
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Folio</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Responsable</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cotizaciones as $cotizacion)
                                <tr>
                                    <td>{{ $cotizacion->creado_en->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('cotizaciones.detalle', $cotizacion) }}">
                                            {{ $cotizacion->folio }}
                                        </a>
                                    </td>
                                    <td>{{ $cotizacion->cliente->nombre }}</td>
                                    <td>{{ $cotizacion->responsable->nombre }}</td>
                                    <td>{{ $cotizacion->etiquetaEstado() }}</td>
                                    <td>${{ number_format((float) $cotizacion->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="estado-vacio" colspan="6">No hay cotizaciones con estos filtros.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('componentes.paginacion', ['paginador' => $cotizaciones])
            </section>

@endsection
