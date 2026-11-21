@extends('layouts.aplicacion')

@section('titulo', $pcArmada->nombre)
@section('contexto_carga', 'formulario')

@section('contenido')
    @php($puedeOperar = auth()->user()->esAdministrador() || auth()->user()->esComercial())
    <section class="bloque-administrativo">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <div>
                <p class="ceja-seccion">PC armada</p>
                <h2>{{ $pcArmada->nombre }}</h2>
                @if ($pcArmada->descripcion)
                    <p>{{ $pcArmada->descripcion }}</p>
                @endif
            </div>
            <div class="grupo-acciones">
                @if ($puedeOperar)
                    <x-boton variante="secundario" :href="route('inventario.pc-armadas.editar', $pcArmada)">Editar</x-boton>
                    <form method="POST" action="{{ route('inventario.pc-armadas.estado', $pcArmada) }}">
                        @csrf
                        @method('PATCH')
                        <x-boton tipo="submit" :variante="$pcArmada->activo ? 'peligro' : 'exito'">{{ $pcArmada->activo ? 'Desactivar' : 'Activar' }}</x-boton>
                    </form>
                @endif
                <x-boton variante="contorno" :href="route('inventario.pc-armadas.listado')">Volver</x-boton>
            </div>
        </div>
        <x-tabla-responsive etiqueta="Componentes de la PC armada" adaptable>
            <table class="tabla-registros tabla-adaptable">
                <thead>
                    <tr>
                        <th scope="col">Apartado</th>
                        <th scope="col">Producto</th>
                        <th scope="col">Código</th>
                        <th scope="col">Cantidad</th>
                        <th scope="col">Precio actual</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pcArmada->componentes as $componente)
                        <tr>
                            <td data-etiqueta="Apartado">{{ \App\Models\Producto::etiquetaTipoComponentePc($componente->tipo_componente_pc) }}</td>
                            <th scope="row" data-etiqueta="Producto">{{ $componente->producto->nombre }}</th>
                            <td data-etiqueta="Código">{{ $componente->producto->codigo }}</td>
                            <td data-etiqueta="Cantidad">{{ $componente->cantidad }}</td>
                            <td data-etiqueta="Precio actual">${{ number_format((float) $componente->producto->precio, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-tabla-responsive>
    </section>
@endsection
