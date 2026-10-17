@extends('layouts.aplicacion')

@section('titulo', 'Registrar categoría')

@section('contenido')
    <x-encabezado-pagina titulo="Registrar categoría">
        <x-slot:acciones><x-boton variante="contorno" :href="route('inventario.categorias.listado')">Regresar al listado</x-boton></x-slot:acciones>
    </x-encabezado-pagina>

    <section class="bloque-administrativo" aria-labelledby="titulo-registrar-categoria">
        <h2 id="titulo-registrar-categoria">Registrar categoría</h2>
        <form class="formulario-administrativo formulario-filtros" method="POST" action="{{ route('inventario.categorias.guardar') }}">
            @csrf
            <x-campo-formulario id="nombre" nombre="nombre" etiqueta="Nombre" :valor="old('nombre')" required maxlength="255" />
            <div class="acciones-formulario">
                <x-boton variante="contorno" :href="route('inventario.categorias.listado')">Cancelar</x-boton>
                <x-boton tipo="submit">Guardar categoría</x-boton>
            </div>
        </form>
    </section>
@endsection
