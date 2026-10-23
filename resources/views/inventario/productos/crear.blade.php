@extends('layouts.aplicacion')

@section('titulo', 'Registrar producto')

@section('contenido')

    <section class="bloque-administrativo" aria-labelledby="titulo-registrar-producto">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <h2 id="titulo-registrar-producto">Registrar producto</h2>
            <div class="grupo-acciones">
                <x-boton variante="contorno" :href="route('inventario.productos.listado')">Regresar al listado</x-boton>
            </div>
        </div>
        <form class="formulario-administrativo formulario-catalogo" method="POST" action="{{ route('inventario.productos.guardar') }}">
            @csrf
            @include('inventario.productos.campos', ['producto' => null])
            <div class="acciones-formulario campo-formulario--ancho">
                <x-boton variante="contorno" :href="route('inventario.productos.listado')">Cancelar</x-boton>
                <x-boton tipo="submit">Guardar producto</x-boton>
            </div>
        </form>
    </section>
@endsection
