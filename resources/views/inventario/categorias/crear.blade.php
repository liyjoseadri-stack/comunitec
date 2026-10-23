@extends('layouts.aplicacion')

@section('titulo', 'Registrar categoría')

@section('contenido')

    <section class="bloque-administrativo" aria-labelledby="titulo-registrar-categoria">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <h2 id="titulo-registrar-categoria">Registrar categoría</h2>
            <div class="grupo-acciones">
                <x-boton variante="contorno" :href="route('inventario.categorias.listado')">Regresar al listado</x-boton>
            </div>
        </div>
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
