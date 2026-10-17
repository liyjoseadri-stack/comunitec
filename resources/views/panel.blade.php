@extends('layouts.aplicacion')

@section('titulo', 'Panel principal')

@section('contenido')
    <x-encabezado-pagina titulo="Panel principal" />

    @if (auth()->user()->esAdministrador())
        <div class="cuadricula-alertas-operativas">
            <section class="tarjeta-alerta-operativa {{ $totalEntregasPorVencer > 0 ? 'tarjeta-alerta-operativa--advertencia' : 'tarjeta-alerta-operativa--correcta' }}" aria-labelledby="titulo-entregas-proximas">
                <header class="tarjeta-alerta-operativa__encabezado">
                    <div>
                        <span class="tarjeta-alerta-operativa__categoria">Entregas</span>
                        <h2 id="titulo-entregas-proximas">Entregas del siguiente día hábil</h2>
                    </div>
                    <span class="contador-alerta" aria-label="{{ $totalEntregasPorVencer }} {{ $totalEntregasPorVencer === 1 ? 'entrega próxima' : 'entregas próximas' }}">{{ $totalEntregasPorVencer }}</span>
                </header>

                @if ($entregasPorVencer->isEmpty())
                    <x-estado-vacio mensaje="No hay entregas programadas para el siguiente día hábil." />
                @else
                    <ul class="lista-alertas-operativas">
                        @foreach ($entregasPorVencer as $cotizacion)
                            <li>
                                <div class="alerta-operativa__contenido">
                                    <a href="{{ route('cotizaciones.detalle', $cotizacion) }}">{{ $cotizacion->folio }}</a>
                                    <span>{{ $cotizacion->cliente->nombre }}</span>
                                </div>
                                <div class="alerta-operativa__meta">
                                    <span class="insignia-severidad insignia-severidad--advertencia">Próxima</span>
                                    <time datetime="{{ $cotizacion->entrega_limite_en->toDateString() }}">{{ $cotizacion->entrega_limite_en->format('d/m/Y') }}</time>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <footer class="tarjeta-alerta-operativa__pie">
                    @if ($totalEntregasPorVencer > $entregasPorVencer->count())
                        <span>Mostrando {{ $entregasPorVencer->count() }} de {{ $totalEntregasPorVencer }}</span>
                    @else
                        <span>{{ $totalEntregasPorVencer }} {{ $totalEntregasPorVencer === 1 ? 'entrega requiere' : 'entregas requieren' }} atención</span>
                    @endif
                    <x-boton variante="contorno" :href="route('cotizaciones.listado', ['estado' => \App\Models\Cotizacion::ESTADO_ACEPTADA])" compacto>Ver cotizaciones aceptadas</x-boton>
                </footer>
            </section>

            <section class="tarjeta-alerta-operativa {{ $totalProductosStockBajo > 0 ? 'tarjeta-alerta-operativa--peligro' : 'tarjeta-alerta-operativa--correcta' }}" aria-labelledby="titulo-alertas-inventario">
                <header class="tarjeta-alerta-operativa__encabezado">
                    <div>
                        <span class="tarjeta-alerta-operativa__categoria">Inventario</span>
                        <h2 id="titulo-alertas-inventario">Productos con stock bajo</h2>
                    </div>
                    <span class="contador-alerta" aria-label="{{ $totalProductosStockBajo }} {{ $totalProductosStockBajo === 1 ? 'producto con stock bajo' : 'productos con stock bajo' }}">{{ $totalProductosStockBajo }}</span>
                </header>

                @if ($productosStockBajo->isEmpty())
                    <x-estado-vacio mensaje="No hay productos con stock bajo." />
                @else
                    <ul class="lista-alertas-operativas">
                        @foreach ($productosStockBajo as $producto)
                            @php
                                $severidad = $producto->existencias === 0 ? 'peligro' : ($producto->existencias <= 2 ? 'critica' : 'baja');
                                $etiquetaSeveridad = $producto->existencias === 0 ? 'Agotado' : ($producto->existencias <= 2 ? 'Crítico' : 'Bajo');
                            @endphp
                            <li>
                                <div class="alerta-operativa__contenido">
                                    <a href="{{ route('inventario.productos.detalle', $producto) }}">{{ $producto->nombre }}</a>
                                    <span>{{ $producto->codigo ?: 'Sin SKU' }}</span>
                                </div>
                                <div class="alerta-operativa__meta">
                                    <span class="insignia-severidad insignia-severidad--{{ $severidad }}">{{ $etiquetaSeveridad }}</span>
                                    <strong>{{ $producto->existencias }} {{ $producto->existencias === 1 ? 'pieza' : 'piezas' }}</strong>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <footer class="tarjeta-alerta-operativa__pie">
                    @if ($totalProductosStockBajo > $productosStockBajo->count())
                        <span>Mostrando {{ $productosStockBajo->count() }} de {{ $totalProductosStockBajo }}</span>
                    @else
                        <span>{{ $totalProductosStockBajo }} {{ $totalProductosStockBajo === 1 ? 'producto requiere' : 'productos requieren' }} atención</span>
                    @endif
                    <x-boton variante="contorno" :href="route('inventario.productos.listado')" compacto>Ver inventario completo</x-boton>
                </footer>
            </section>
        </div>
    @else
        <section class="bloque-administrativo accesos-panel" aria-labelledby="titulo-accesos-panel">
            <div class="encabezado-seccion">
                <div>
                    <h2 id="titulo-accesos-panel">Accesos de trabajo</h2>
                    <p>Continúa con las operaciones disponibles para tu rol.</p>
                </div>
            </div>
            <div class="cuadricula-accesos-panel">
                @if (auth()->user()->esComercial())
                    <x-boton :href="route('cotizaciones.crear')">Nueva cotización</x-boton>
                @endif
                <x-boton variante="contorno" :href="route('cotizaciones.listado')">Consultar cotizaciones</x-boton>
                <x-boton variante="contorno" :href="route('ventas.listado')">Consultar ventas</x-boton>
                <x-boton variante="contorno" :href="route('reportes.resumen')">Consultar reportes</x-boton>
            </div>
        </section>
    @endif
@endsection
