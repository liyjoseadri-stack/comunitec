@extends('layouts.aplicacion')

@section('titulo', 'Editar usuario')

@section('contenido')

    <section class="bloque-administrativo" aria-labelledby="titulo-editar-usuario">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <h2 id="titulo-editar-usuario">Datos de {{ $usuario->nombre }}</h2>
            <div class="grupo-acciones">
                <x-boton variante="contorno" :href="route('administracion.usuarios.listado')">Regresar al listado</x-boton>
            </div>
        </div>
        <form class="formulario-administrativo formulario-dos-columnas" method="POST" action="{{ route('administracion.usuarios.actualizar', $usuario) }}">
            @csrf
            @method('PUT')
            @include('administracion.usuarios.campos', ['usuario' => $usuario])
            <div class="acciones-formulario campo-formulario--ancho">
                <x-boton variante="contorno" :href="route('administracion.usuarios.listado')">Cancelar</x-boton>
                <x-boton tipo="submit">Guardar cambios</x-boton>
            </div>
        </form>
    </section>
@endsection
