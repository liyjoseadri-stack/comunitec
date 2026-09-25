@extends('layouts.aplicacion')

@section('titulo', 'Panel')

@section('contenido')
            <header class="encabezado-pagina">
                <div>
                    <h1>Panel principal</h1>
                    <p>Bienvenido, {{ auth()->user()->nombre }}.</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="boton-secundario" type="submit">Cerrar sesión</button>
                </form>
            </header>

            @include('componentes.errores-validacion')

            <section class="bloque-administrativo" aria-labelledby="titulo-periodo">
                <h2 id="titulo-periodo">Resumen de {{ $periodo->etiqueta() }}</h2>
                <form class="formulario-filtros" method="GET" action="{{ route('panel') }}">
                    <div class="campo-filtro">
                        <label for="mes-panel">Mes</label>
                        <input id="mes-panel" name="mes" type="month" value="{{ $periodo->mes() }}">
                    </div>
                    <button type="submit">Consultar</button>
                </form>
                <nav class="navegacion-periodo" aria-label="Cambiar mes del panel">
                    <a href="{{ route('panel', ['mes' => $periodo->anterior()]) }}">Mes anterior</a>
                    <a href="{{ route('panel', ['mes' => $periodo->siguiente()]) }}">Mes siguiente</a>
                </nav>
            </section>

            <section class="cuadricula-indicadores" aria-label="Indicadores mensuales">
                <a class="tarjeta-indicador" href="{{ route('reportes.cotizaciones', [
                    'desde' => $periodo->inicio->format('Y-m-d'),
                    'hasta' => $periodo->finExclusivo->subDay()->format('Y-m-d'),
                ]) }}">
                    <span>Cotizaciones</span>
                    <strong>{{ $totalCotizaciones }}</strong>
                </a>
                @foreach ([
                    'borrador' => 'Borradores',
                    'pendiente' => 'Pendientes',
                    'aceptada' => 'Aceptadas',
                    'rechazada' => 'Rechazadas',
                    'cancelada' => 'Canceladas',
                    'vencida' => 'Vencidas',
                ] as $estado => $etiqueta)
                    <a class="tarjeta-indicador" href="{{ route('reportes.cotizaciones', [
                        'desde' => $periodo->inicio->format('Y-m-d'),
                        'hasta' => $periodo->finExclusivo->subDay()->format('Y-m-d'),
                        'estado' => $estado,
                    ]) }}">
                        <span>{{ $etiqueta }}</span>
                        <strong>{{ $resumenCotizaciones[$estado] }}</strong>
                    </a>
                @endforeach
                <article class="tarjeta-indicador">
                    <h2>Aceptación</h2>
                    <strong>{{ number_format($porcentajeAceptacion, 1) }}%</strong>
                </article>
                <a class="tarjeta-indicador" href="{{ route('reportes.ventas', [
                    'desde' => $periodo->inicio->format('Y-m-d'),
                    'hasta' => $periodo->finExclusivo->subDay()->format('Y-m-d'),
                ]) }}">
                    <span>Ventas</span>
                    <strong>{{ $cantidadVentas }}</strong>
                </a>
                <article class="tarjeta-indicador">
                    <h2>Total vendido</h2>
                    <strong>${{ number_format($totalVentas, 2) }}</strong>
                </article>
            </section>

            <div class="cuadricula-actividad">
                <section class="bloque-administrativo" aria-labelledby="titulo-cotizaciones-recientes">
                    <h2 id="titulo-cotizaciones-recientes">Cotizaciones recientes</h2>
                    <ul class="lista-actividad">
                        @forelse ($cotizacionesRecientes as $cotizacion)
                            <li>
                                <a href="{{ route('cotizaciones.detalle', $cotizacion) }}">
                                    {{ $cotizacion->folio }}
                                </a>
                                <span>{{ $cotizacion->cliente->nombre }} · {{ $cotizacion->etiquetaEstado() }}</span>
                            </li>
                        @empty
                            <li>No hay cotizaciones en este mes.</li>
                        @endforelse
                    </ul>
                </section>

                <section class="bloque-administrativo" aria-labelledby="titulo-ventas-recientes">
                    <h2 id="titulo-ventas-recientes">Ventas recientes</h2>
                    <ul class="lista-actividad">
                        @forelse ($ventasRecientes as $venta)
                            <li>
                                <a href="{{ route('ventas.detalle', $venta) }}">{{ $venta->folio }}</a>
                                <span>{{ $venta->nombreClienteMostrado() }} · ${{ number_format((float) $venta->total, 2) }}</span>
                            </li>
                        @empty
                            <li>No hay ventas en este mes.</li>
                        @endforelse
                    </ul>
                </section>
            </div>

            <section class="bloque-administrativo" aria-labelledby="titulo-alertas-inventario">
                <h2 id="titulo-alertas-inventario">Alertas de inventario</h2>
                @if ($productosStockBajo->isEmpty())
                    <p>No hay productos con stock bajo.</p>
                @else
                    <ul class="lista-alertas">
                        @foreach ($productosStockBajo as $articulo)
                            <li>{{ $articulo->nombre }}: {{ $articulo->cantidad_piezas_disponibles }} piezas disponibles.</li>
                        @endforeach
                    </ul>
                @endif
            </section>

@endsection
