@extends('layouts.aplicacion')

@section('titulo', 'Editar producto')

@section('contenido')

    <section class="bloque-administrativo" aria-labelledby="titulo-editar-producto">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <h2 id="titulo-editar-producto">Editar producto</h2>
            <div class="grupo-acciones">
                <x-boton variante="contorno" :href="route('inventario.productos.detalle', $producto)">Cancelar</x-boton>
            </div>
        </div>
        <form class="formulario-administrativo formulario-catalogo" method="POST" action="{{ route('inventario.productos.actualizar', $producto) }}">
            @csrf
            @method('PUT')
            @include('inventario.productos.campos')
            <div class="acciones-formulario campo-formulario--ancho">
                <x-boton variante="contorno" :href="route('inventario.productos.detalle', $producto)">Cancelar</x-boton>
                <x-boton tipo="submit">Guardar cambios</x-boton>
            </div>
        </form>
    </section>
@endsection
