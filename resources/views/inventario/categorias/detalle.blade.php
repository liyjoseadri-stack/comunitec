@extends('layouts.aplicacion')

@section('titulo', $categoria->nombre)

@section('contenido')
    <x-encabezado-pagina :titulo="$categoria->nombre" descripcion="Detalle y artículos asociados a la categoría.">
        <x-slot:acciones>
            <x-boton variante="contorno" :href="route('inventario.categorias.listado')">Regresar</x-boton>
        </x-slot:acciones>
    </x-encabezado-pagina>

    @include('componentes.errores-validacion')
    @if (session('success'))<p class="mensaje-exito" role="status">{{ session('success') }}</p>@endif

    <section class="bloque-administrativo">
        <h2>Editar categoría</h2>
        <form class="formulario-administrativo formulario-filtros" method="POST" action="{{ route('inventario.categorias.actualizar', $categoria) }}">
            @csrf @method('PUT')
            <div class="campo-formulario">
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre" value="{{ old('nombre', $categoria->nombre) }}" required maxlength="255">
            </div>
            <x-boton tipo="submit">Guardar cambios</x-boton>
        </form>
    </section>

    <section class="bloque-administrativo">
        <h2>Productos y servicios</h2>
        <div class="contenedor-tabla" tabindex="0">
            <table class="tabla-registros">
                <thead><tr><th>SKU</th><th>Nombre</th><th>Tipo</th><th>Estado</th></tr></thead>
                <tbody>
                    @forelse ($categoria->articulos as $articulo)
                        <tr><td>{{ $articulo->codigo }}</td><td>{{ $articulo->nombre }}</td><td>{{ ucfirst($articulo->tipo) }}</td><td>{{ $articulo->activo ? 'Activo' : 'Inactivo' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="estado-vacio">Esta categoría todavía no tiene artículos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
