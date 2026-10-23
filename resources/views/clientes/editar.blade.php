@extends('layouts.aplicacion')

@section('titulo', 'Editar cliente')

@section('contenido')

    <section class="bloque-administrativo" aria-labelledby="titulo-editar-cliente">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <h2 id="titulo-editar-cliente">Editar cliente</h2>
            <div class="grupo-acciones">
                <x-boton variante="contorno" :href="route('clientes.listado')">Regresar al listado</x-boton>
            </div>
        </div>
        <form class="formulario-administrativo formulario-dos-columnas" method="POST" action="{{ route('clientes.actualizar', $cliente) }}">
            @csrf
            @method('PUT')
            @include('clientes.campos', ['cliente' => $cliente])
            <div class="acciones-formulario campo-formulario--ancho">
                <x-boton variante="contorno" :href="route('clientes.listado')">Cancelar</x-boton>
                <x-boton tipo="submit">Guardar cambios</x-boton>
            </div>
        </form>
    </section>
@endsection
