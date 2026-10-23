@extends('layouts.aplicacion')

@section('titulo', 'Editar servicio')

@section('contenido')

    <section class="bloque-administrativo" aria-labelledby="titulo-editar-servicio">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <h2 id="titulo-editar-servicio">Editar servicio</h2>
            <div class="grupo-acciones">
                <x-boton variante="contorno" :href="route('inventario.servicios.detalle', $servicio)">Cancelar</x-boton>
            </div>
        </div>
        <form class="formulario-administrativo formulario-catalogo" method="POST" action="{{ route('inventario.servicios.actualizar', $servicio) }}">
            @csrf
            @method('PUT')
            @include('inventario.servicios.campos')
            <div class="acciones-formulario campo-formulario--ancho">
                <x-boton variante="contorno" :href="route('inventario.servicios.detalle', $servicio)">Cancelar</x-boton>
                <x-boton tipo="submit">Guardar cambios</x-boton>
            </div>
        </form>
    </section>
@endsection
