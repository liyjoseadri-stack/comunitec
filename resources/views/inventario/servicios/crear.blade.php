@extends('layouts.aplicacion')

@section('titulo', 'Registrar servicio')

@section('contenido')

    <section class="bloque-administrativo" aria-labelledby="titulo-registrar-servicio">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <h2 id="titulo-registrar-servicio">Registrar servicio</h2>
            <div class="grupo-acciones">
                <x-boton variante="contorno" :href="route('inventario.servicios.listado')">Regresar al listado</x-boton>
            </div>
        </div>
        <form class="formulario-administrativo formulario-catalogo" method="POST" action="{{ route('inventario.servicios.guardar') }}">
            @csrf
            @include('inventario.servicios.campos', ['servicio' => null])
            <div class="acciones-formulario campo-formulario--ancho">
                <x-boton variante="contorno" :href="route('inventario.servicios.listado')">Cancelar</x-boton>
                <x-boton tipo="submit">Guardar servicio</x-boton>
            </div>
        </form>
    </section>
@endsection
