@extends('layouts.aplicacion')

@section('titulo', 'Servicios')

@section('contenido')
    <section class="bloque-administrativo">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <h2>Servicios registrados <span class="contador-titulo">{{ $servicios->total() }}</span></h2>
            <div class="grupo-acciones">
                <x-boton :href="route('inventario.servicios.crear')">Registrar servicio</x-boton>
            </div>
        </div>
        <form class="formulario-filtros" method="GET">
            <div class="campo-formulario">
                <label for="buscar">Buscar</label>
                <input id="buscar" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o código">
            </div>
            <div class="campo-formulario">
                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria">
                    <option value="">Todas</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected(request('categoria') == $categoria->id)>{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="campo-formulario">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="activos" @selected(request('estado') === 'activos')>Activos</option>
                    <option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option>
                </select>
            </div>
            <x-boton tipo="submit" variante="secundario">Filtrar</x-boton>
            @if (request()->filled('buscar') || request()->filled('categoria') || request()->filled('estado'))
                <x-boton variante="contorno" :href="route('inventario.servicios.listado')">Limpiar</x-boton>
            @endif
        </form>
        <x-tabla-responsive etiqueta="Servicios registrados" adaptable>
            <table class="tabla-registros tabla-adaptable">
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Servicio</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">Unidad</th>
                        <th scope="col">Precio</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($servicios as $servicio)
                        <tr>
                            <td data-etiqueta="Código">{{ $servicio->codigo }}</td>
                            <th scope="row" data-etiqueta="Servicio"><strong>{{ $servicio->nombre }}</strong></th>
                            <td data-etiqueta="Categoría">{{ $servicio->categoria?->nombre }}</td>
                            <td data-etiqueta="Unidad">{{ $servicio->unidad }}</td>
                            <td data-etiqueta="Precio">${{ number_format((float) $servicio->precio, 2) }}</td>
                            <td data-etiqueta="Estado"><x-insignia-estado :estado="$servicio->activo ? 'activo' : 'inactivo'" /></td>
                            <td data-etiqueta="Acciones" class="acciones-tabla">
                                <x-grupo-acciones-fila>
                                    <x-boton compacto variante="contorno" :href="route('inventario.servicios.detalle', $servicio)">Ver</x-boton>
                                    <x-boton compacto variante="secundario" :href="route('inventario.servicios.editar', $servicio)">Editar</x-boton>
                                    <form method="POST" action="{{ route('inventario.servicios.estado', $servicio) }}"
                                        @if ($servicio->activo)
                                            data-confirmar data-confirmar-titulo="Desactivar servicio"
                                            data-confirmar-mensaje="{{ $servicio->nombre }} dejará de estar disponible para agregar a nuevas cotizaciones. Las partidas existentes conservarán sus datos históricos."
                                            data-confirmar-etiqueta="Desactivar"
                                        @endif
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <x-boton compacto tipo="submit" :variante="$servicio->activo ? 'peligro' : 'exito'">{{ $servicio->activo ? 'Desactivar' : 'Activar' }}</x-boton>
                                    </form>
                                </x-grupo-acciones-fila>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tabla-estado-vacio">
                                <x-estado-vacio mensaje="No hay servicios con estos filtros.">
                                    <x-slot:acciones>
                                        <x-boton variante="contorno" :href="route('inventario.servicios.crear')">Registrar servicio</x-boton>
                                    </x-slot:acciones>
                                </x-estado-vacio>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-tabla-responsive>
        @include('componentes.paginacion', ['paginador' => $servicios])
    </section>
@endsection
