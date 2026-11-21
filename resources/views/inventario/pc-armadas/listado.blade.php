@extends('layouts.aplicacion')

@section('titulo', 'PC armadas')
@section('contexto_carga', 'listado')

@section('contenido')
    @php($puedeOperar = auth()->user()->esAdministrador() || auth()->user()->esComercial())
    <section class="bloque-administrativo">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <div>
                <p class="ceja-seccion">Inventario</p>
                <h2>PC armadas <span class="contador-titulo">{{ $pcArmadas->total() }}</span></h2>
            </div>
            @if ($puedeOperar)
                <x-boton :href="route('inventario.pc-armadas.crear')">Nueva PC armada</x-boton>
            @endif
        </div>
        <form class="formulario-filtros" method="GET">
            <x-campo-formulario id="buscar" nombre="buscar" etiqueta="Buscar" :valor="request('buscar')" placeholder="Nombre o descripción" />
            <x-campo-formulario id="estado" nombre="estado" etiqueta="Estado" tipo="select">
                <option value="">Todos</option>
                <option value="activas" @selected(request('estado') === 'activas')>Activas</option>
                <option value="inactivas" @selected(request('estado') === 'inactivas')>Inactivas</option>
            </x-campo-formulario>
            <x-boton tipo="submit" variante="secundario">Filtrar</x-boton>
            @if (request()->filled('buscar') || request()->filled('estado'))
                <x-boton variante="contorno" :href="route('inventario.pc-armadas.listado')">Limpiar</x-boton>
            @endif
        </form>
        <x-tabla-responsive etiqueta="PC armadas registradas" adaptable>
            <table class="tabla-registros tabla-adaptable">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Componentes</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pcArmadas as $pcArmada)
                        <tr>
                            <th scope="row" data-etiqueta="Nombre"><strong>{{ $pcArmada->nombre }}</strong></th>
                            <td data-etiqueta="Componentes">{{ $pcArmada->componentes->count() }}</td>
                            <td data-etiqueta="Estado"><x-insignia-estado :estado="$pcArmada->activo ? 'activo' : 'inactivo'" /></td>
                            <td data-etiqueta="Acciones" class="acciones-tabla">
                                <x-grupo-acciones-fila>
                                    <x-boton compacto variante="contorno" :href="route('inventario.pc-armadas.detalle', $pcArmada)">Ver</x-boton>
                                    @if ($puedeOperar)
                                        <x-boton compacto variante="secundario" :href="route('inventario.pc-armadas.editar', $pcArmada)">Editar</x-boton>
                                    @endif
                                </x-grupo-acciones-fila>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-estado-vacio mensaje="Aún no hay PC armadas registradas." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-tabla-responsive>
        {{ $pcArmadas->links() }}
    </section>
@endsection
