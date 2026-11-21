@extends('layouts.aplicacion')

@section('titulo', 'Nueva PC armada')
@section('contexto_carga', 'formulario')

@section('contenido')
    <section class="bloque-administrativo">
        <div class="encabezado-seccion">
            <div>
                <p class="ceja-seccion">Inventario</p>
                <h2>Registrar PC armada</h2>
            </div>
        </div>
        <form method="POST" action="{{ route('inventario.pc-armadas.guardar') }}" class="formulario-administrativo">
            @csrf
            @include('inventario.pc-armadas.campos')
            <div class="grupo-acciones formulario-administrativo__acciones">
                <x-boton variante="contorno" :href="route('inventario.pc-armadas.listado')">Cancelar</x-boton>
                <x-boton tipo="submit">Guardar PC armada</x-boton>
            </div>
        </form>
    </section>
@endsection
