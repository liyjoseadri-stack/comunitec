@extends('layouts.aplicacion')

@section('titulo', 'Editar categoría')

@section('contenido')
    <x-encabezado-pagina titulo="Editar categoría">
        <x-slot:acciones><x-boton variante="contorno" :href="route('inventario.categorias.detalle', $categoria)">Cancelar</x-boton></x-slot:acciones>
    </x-encabezado-pagina>

    <section class="bloque-administrativo" aria-labelledby="titulo-editar-categoria">
        <h2 id="titulo-editar-categoria">Editar categoría</h2>
        <form class="formulario-administrativo formulario-filtros" method="POST" action="{{ route('inventario.categorias.actualizar', $categoria) }}">
            @csrf
            @method('PUT')
            <x-campo-formulario id="nombre" nombre="nombre" etiqueta="Nombre" :valor="old('nombre', $categoria->nombre)" required maxlength="255" />
            <div class="acciones-formulario">
                <x-boton variante="contorno" :href="route('inventario.categorias.detalle', $categoria)">Cancelar</x-boton>
                <x-boton tipo="submit">Guardar cambios</x-boton>
            </div>
        </form>
    </section>
@endsection
