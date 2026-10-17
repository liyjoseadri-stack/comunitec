@extends('layouts.aplicacion')

@section('titulo', 'Registrar cliente')

@section('contenido')
    <x-encabezado-pagina titulo="Registrar cliente">
        <x-slot:acciones><x-boton variante="contorno" :href="route('clientes.listado')">Regresar al listado</x-boton></x-slot:acciones>
    </x-encabezado-pagina>

    <section class="bloque-administrativo" aria-labelledby="titulo-registrar-cliente">
        <h2 id="titulo-registrar-cliente">Registrar cliente</h2>
        <form class="formulario-administrativo formulario-dos-columnas" method="POST" action="{{ route('clientes.guardar') }}">
            @csrf
            @include('clientes.campos', ['cliente' => null])
            <div class="acciones-formulario campo-formulario--ancho">
                <x-boton variante="contorno" :href="route('clientes.listado')">Cancelar</x-boton>
                <x-boton tipo="submit">Guardar cliente</x-boton>
            </div>
        </form>
    </section>
@endsection
