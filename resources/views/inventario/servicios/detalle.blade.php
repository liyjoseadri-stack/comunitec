@extends('layouts.aplicacion')

@section('titulo', $servicio->nombre)

@section('contenido')
    <x-encabezado-pagina :titulo="$servicio->nombre">
        <x-slot:acciones>
            <x-boton variante="contorno" :href="route('inventario.servicios.listado')">Regresar al listado</x-boton>
            <x-boton :href="route('inventario.servicios.editar', $servicio)">Editar servicio</x-boton>
        </x-slot:acciones>
    </x-encabezado-pagina>


    <section class="bloque-administrativo" aria-labelledby="titulo-informacion-servicio">
        <div class="encabezado-seccion">
            <h2 id="titulo-informacion-servicio">Información del servicio</h2>
            <x-insignia-estado :estado="$servicio->activo ? 'activo' : 'inactivo'" />
        </div>

        <dl class="cuadricula-detalle">
            <div><dt>Nombre</dt><dd>{{ $servicio->nombre }}</dd></div>
            <div><dt>Código</dt><dd>{{ $servicio->codigo }}</dd></div>
            <div><dt>Categoría</dt><dd>{{ $servicio->categoria?->nombre ?? 'Sin categoría' }}</dd></div>
            <div><dt>Unidad de cobro</dt><dd>{{ $servicio->unidad }}</dd></div>
            <div><dt>Precio con IVA</dt><dd>${{ number_format((float) $servicio->precio, 2) }}</dd></div>
            <div><dt>Cotizaciones relacionadas</dt><dd>{{ $servicio->partidas_cotizacion_count }}</dd></div>
            <div><dt>Ventas relacionadas</dt><dd>{{ $servicio->partidas_venta_count }}</dd></div>
            <div class="detalle-ancho"><dt>Descripción o alcance</dt><dd>{{ $servicio->descripcion ?: 'Sin descripción' }}</dd></div>
        </dl>
    </section>
@endsection
