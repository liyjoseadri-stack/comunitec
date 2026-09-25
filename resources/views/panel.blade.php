@extends('layouts.aplicacion')

@section('titulo', 'Panel principal')

@section('contenido')
    <header class="encabezado-pagina">
        <p>Bienvenido, {{ auth()->user()->nombre }}.</p>
    </header>

    @if (auth()->user()->esAdministrador())
        <section class="bloque-administrativo" aria-labelledby="titulo-entregas-proximas">
            <h2 id="titulo-entregas-proximas">Recordatorios de entrega</h2>
            @if ($entregasPorVencer->isEmpty())
                <p class="estado-vacio">No hay entregas que venzan el siguiente día hábil.</p>
            @else
                <ul class="lista-alertas">
                    @foreach ($entregasPorVencer as $cotizacion)
                        <li>
                            Entregar productos o servicios de
                            <a href="{{ route('cotizaciones.detalle', $cotizacion) }}">{{ $cotizacion->folio }}</a>
                            a {{ $cotizacion->cliente->nombre }} antes del
                            {{ $cotizacion->entrega_limite_en->format('d/m/Y') }}.
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="bloque-administrativo" aria-labelledby="titulo-alertas-inventario">
            <h2 id="titulo-alertas-inventario">Alertas de inventario</h2>
            @if ($productosStockBajo->isEmpty())
                <p class="estado-vacio">No hay productos con stock bajo.</p>
            @else
                <ul class="lista-alertas">
                    @foreach ($productosStockBajo as $producto)
                        <li>
                            <a href="{{ route('inventario.productos.detalle', $producto) }}">{{ $producto->nombre }}</a>:
                            {{ $producto->existencias }} piezas disponibles.
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @else
        <section class="bloque-administrativo">
            <h2>Accesos rápidos</h2>
            <div class="grupo-acciones">
                <x-boton :href="route('cotizaciones.listado')">Cotizaciones</x-boton>
                <x-boton variante="contorno" :href="route('ventas.listado')">Ventas</x-boton>
                <x-boton variante="contorno" :href="route('reportes.resumen')">Reportes</x-boton>
            </div>
        </section>
    @endif
@endsection
