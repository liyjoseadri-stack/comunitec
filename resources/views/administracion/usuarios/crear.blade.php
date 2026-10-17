@extends('layouts.aplicacion')

@section('titulo', 'Crear usuario')

@section('contenido')
    <x-encabezado-pagina titulo="Crear usuario">
        <x-slot:acciones><x-boton variante="contorno" :href="route('administracion.usuarios.listado')">Regresar al listado</x-boton></x-slot:acciones>
    </x-encabezado-pagina>

    <section class="bloque-administrativo" aria-labelledby="titulo-crear-usuario">
        <h2 id="titulo-crear-usuario">Datos de acceso</h2>
        <form class="formulario-administrativo formulario-dos-columnas" method="POST" action="{{ route('administracion.usuarios.guardar') }}">
            @csrf
            @include('administracion.usuarios.campos', ['usuario' => null])
            <div class="acciones-formulario campo-formulario--ancho">
                <x-boton variante="contorno" :href="route('administracion.usuarios.listado')">Cancelar</x-boton>
                <x-boton tipo="submit">Crear usuario</x-boton>
            </div>
        </form>
    </section>
@endsection
