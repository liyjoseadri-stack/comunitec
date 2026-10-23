@extends('layouts.aplicacion')

@section('titulo', 'Resumen de reportes')

@section('contenido')

    <section class="selector-periodo" aria-label="Indicadores mensuales" aria-labelledby="titulo-periodo">
        <div class="selector-periodo__encabezado">
            <div>
                <span class="selector-periodo__etiqueta">Periodo consultado</span>
                <h2 id="titulo-periodo">{{ ucfirst($periodo->etiqueta()) }}</h2>
            </div>
            <nav class="navegacion-periodo" aria-label="Cambiar mes del reporte">
                <a class="boton boton--secundario boton--compacto" href="{{ route('reportes.resumen', ['mes' => $periodo->anterior()]) }}" aria-label="Consultar mes anterior">
                    <span aria-hidden="true">←</span>
                    Mes anterior
                </a>
                <a class="boton boton--secundario boton--compacto" href="{{ route('reportes.resumen', ['mes' => $periodo->siguiente()]) }}" aria-label="Consultar mes siguiente">
                    Mes siguiente
                    <span aria-hidden="true">→</span>
                </a>
            </nav>
        </div>

        <form class="formulario-periodo" method="GET" action="{{ route('reportes.resumen') }}">
            <x-campo-formulario id="mes-reporte" nombre="mes" etiqueta="Seleccionar otro mes" tipo="month" contenedor="campo-filtro" :valor="old('mes', $periodo->mes())" />
            <button class="boton" type="submit">Consultar periodo</button>
        </form>
    </section>

    <section
        class="grupo-indicadores"
        aria-labelledby="titulo-cotizaciones"
        aria-describedby="descripcion-cotizaciones"
    >
        <div class="encabezado-seccion encabezado-indicadores">
            <div>
                <p class="sobrelinea">Seguimiento comercial</p>
                <h2 id="titulo-cotizaciones">Cotizaciones</h2>
                <p id="descripcion-cotizaciones" class="texto-solo-lectores">
                    Desglose por estado de las cotizaciones registradas en el periodo.
                </p>
            </div>
            <a class="enlace-reporte" href="{{ route('reportes.cotizaciones', ['desde' => $periodo->inicio->format('Y-m-d'), 'hasta' => $periodo->finExclusivo->subDay()->format('Y-m-d')]) }}">
                Ver reporte completo <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="cuadricula-indicadores cuadricula-indicadores--cotizaciones">
            <a class="tarjeta-indicador tarjeta-indicador--principal" href="{{ route('reportes.cotizaciones', ['desde' => $periodo->inicio->format('Y-m-d'), 'hasta' => $periodo->finExclusivo->subDay()->format('Y-m-d')]) }}">
                <span>Total de cotizaciones</span>
                <strong>{{ $totalCotizaciones }}</strong>
                <small>Registradas en el periodo</small>
            </a>
            @foreach (['borrador' => 'Borradores', 'pendiente' => 'Pendientes', 'aceptada' => 'Aceptadas', 'rechazada' => 'Rechazadas', 'cancelada' => 'Canceladas', 'vencida' => 'Vencidas', 'venta' => 'Convertidas en venta'] as $estado => $etiqueta)
                <a class="tarjeta-indicador tarjeta-indicador--{{ $estado }}" href="{{ route('reportes.cotizaciones', ['desde' => $periodo->inicio->format('Y-m-d'), 'hasta' => $periodo->finExclusivo->subDay()->format('Y-m-d'), 'estado' => $estado]) }}">
                    <span>{{ $etiqueta }}</span>
                    <strong>{{ $resumenCotizaciones[$estado] }}</strong>
                    <small>Consultar registros</small>
                </a>
            @endforeach
        </div>
    </section>

    <section
        class="grupo-indicadores"
        aria-labelledby="titulo-ventas"
        aria-describedby="descripcion-ventas"
    >
        <div class="encabezado-seccion encabezado-indicadores">
            <div>
                <p class="sobrelinea">Resultados del periodo</p>
                <h2 id="titulo-ventas">Ventas y conversión</h2>
                <p id="descripcion-ventas" class="texto-solo-lectores">
                    Aceptación, operaciones cerradas e importe acumulado del periodo.
                </p>
            </div>
            <a class="enlace-reporte" href="{{ route('reportes.ventas', ['desde' => $periodo->inicio->format('Y-m-d'), 'hasta' => $periodo->finExclusivo->subDay()->format('Y-m-d')]) }}">
                Ver reporte de ventas <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="cuadricula-indicadores cuadricula-indicadores--ventas">
            <article class="tarjeta-indicador tarjeta-indicador--conversion">
                <span>Porcentaje de aceptación</span>
                <strong>{{ number_format($porcentajeAceptacion, 1) }}%</strong>
                <small>Sobre las cotizaciones del mes</small>
            </article>
            <a class="tarjeta-indicador tarjeta-indicador--ventas" href="{{ route('reportes.ventas', ['desde' => $periodo->inicio->format('Y-m-d'), 'hasta' => $periodo->finExclusivo->subDay()->format('Y-m-d')]) }}">
                <span>Ventas registradas</span>
                <strong>{{ $cantidadVentas }}</strong>
                <small>Operaciones cerradas</small>
            </a>
            <article class="tarjeta-indicador tarjeta-indicador--importe">
                <span>Total vendido</span>
                <strong class="importe-indicador">${{ number_format($totalVentas, 2) }}</strong>
                <small>Importe acumulado del periodo</small>
            </article>
        </div>
    </section>

    <section
        class="actividad-reciente"
        aria-labelledby="titulo-actividad"
        aria-describedby="descripcion-actividad"
    >
        <div class="encabezado-seccion encabezado-indicadores">
            <div>
                <p class="sobrelinea">Últimos movimientos</p>
                <h2 id="titulo-actividad">Actividad reciente</h2>
                <p id="descripcion-actividad" class="texto-solo-lectores">
                    Los movimientos más recientes del mes seleccionado.
                </p>
            </div>
        </div>

        <div class="cuadricula-actividad">
            <section class="panel-actividad" aria-labelledby="titulo-cotizaciones-recientes">
                <div class="panel-actividad__encabezado">
                    <h3 id="titulo-cotizaciones-recientes">Cotizaciones recientes <span class="contador-titulo">{{ $cotizacionesRecientes->count() }}</span></h3>
                </div>
                <ul class="lista-actividad">
                    @forelse ($cotizacionesRecientes as $cotizacion)
                        <li>
                            <span class="actividad-icono" aria-hidden="true">C</span>
                            <div class="actividad-contenido">
                                <a href="{{ route('cotizaciones.detalle', $cotizacion) }}">{{ $cotizacion->folio }}</a>
                                <span>{{ $cotizacion->cliente->nombre }}</span>
                            </div>
                            <x-insignia-estado :estado="$cotizacion->estado" :etiqueta="$cotizacion->etiquetaEstado()" />
                        </li>
                    @empty
                        <li class="actividad-vacia">
                            <x-estado-vacio mensaje="No hay cotizaciones en este mes." />
                        </li>
                    @endforelse
                </ul>
            </section>

            <section class="panel-actividad" aria-labelledby="titulo-ventas-recientes">
                <div class="panel-actividad__encabezado">
                    <h3 id="titulo-ventas-recientes">Ventas recientes <span class="contador-titulo">{{ $ventasRecientes->count() }}</span></h3>
                </div>
                <ul class="lista-actividad">
                    @forelse ($ventasRecientes as $venta)
                        <li>
                            <span class="actividad-icono actividad-icono--venta" aria-hidden="true">V</span>
                            <div class="actividad-contenido">
                                <a href="{{ route('ventas.detalle', $venta) }}">{{ $venta->folio }}</a>
                                <span>{{ $venta->nombreClienteMostrado() }}</span>
                            </div>
                            <strong class="actividad-importe">${{ number_format((float) $venta->total, 2) }}</strong>
                        </li>
                    @empty
                        <li class="actividad-vacia">
                            <x-estado-vacio mensaje="No hay ventas en este mes." />
                        </li>
                    @endforelse
                </ul>
            </section>
        </div>
    </section>
@endsection
