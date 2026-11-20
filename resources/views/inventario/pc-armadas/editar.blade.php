@extends('layouts.aplicacion')

@section('titulo', 'Editar PC armada')
@section('contexto_carga', 'formulario')

@section('contenido')
    <section class="bloque-administrativo">
        <div class="encabezado-seccion">
            <div>
                <p class="ceja-seccion">Inventario</p>
                <h2>Editar PC armada</h2>
            </div>
        </div>
        <form method="POST" action="{{ route('inventario.pc-armadas.actualizar', $pcArmada) }}" class="formulario-administrativo">
            @csrf
            @method('PUT')
            @include('inventario.pc-armadas.campos')
            <div class="grupo-acciones formulario-administrativo__acciones">
                <x-boton variante="contorno" :href="route('inventario.pc-armadas.detalle', $pcArmada)">Cancelar</x-boton>
                <x-boton tipo="submit">Guardar cambios</x-boton>
            </div>
        </form>
    </section>
@endsection
