@extends('layouts.aplicacion')

@section('titulo', $servicio->nombre)

@section('contenido')
    <x-encabezado-pagina :titulo="$servicio->nombre" :descripcion="'Código '.$servicio->codigo">
        <x-slot:acciones>
            <x-boton variante="contorno" :href="route('inventario.servicios.listado')">Regresar</x-boton>
        </x-slot:acciones>
    </x-encabezado-pagina>

    @include('componentes.errores-validacion')
    @if (session('success'))
        <p class="mensaje-exito">{{ session('success') }}</p>
    @endif

    <section class="bloque-administrativo">
        <h2>Editar servicio</h2>
        <form class="formulario-administrativo formulario-catalogo" method="POST" action="{{ route('inventario.servicios.actualizar', $servicio) }}">
            @csrf
            @method('PUT')
            @include('inventario.servicios.campos', ['servicio' => $servicio])
            <x-boton tipo="submit">Guardar cambios</x-boton>
        </form>
    </section>
@endsection
