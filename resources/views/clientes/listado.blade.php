@extends('layouts.aplicacion')

@section('titulo', 'Clientes')

@section('contenido')
    <section class="bloque-administrativo" aria-labelledby="titulo-clientes-registrados">
        <div class="encabezado-seccion encabezado-seccion--acciones">
            <h2 id="titulo-clientes-registrados">Clientes registrados <span class="contador-titulo">{{ $clientes->total() }}</span></h2>
            <div class="grupo-acciones">
                <x-boton :href="route('clientes.crear')">Registrar cliente</x-boton>
            </div>
        </div>

        <form class="formulario-filtros" method="GET" action="{{ route('clientes.listado') }}">
            <x-campo-formulario id="buscar-clientes" nombre="buscar" etiqueta="Buscar clientes" :valor="request('buscar')" placeholder="Nombre, RFC, correo, teléfono o dirección" />
            <x-grupo-acciones-fila>
                <x-boton tipo="submit" variante="secundario">Buscar</x-boton>
                @if (request()->filled('buscar'))
                    <x-boton variante="contorno" :href="route('clientes.listado')">Limpiar</x-boton>
                @endif
            </x-grupo-acciones-fila>
        </form>

        <x-tabla-responsive etiqueta="Clientes registrados" adaptable :vacia="$clientes->isEmpty()">
            <table class="tabla-registros tabla-adaptable tabla-catalogo">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">RFC</th>
                        <th scope="col">Contacto</th>
                        <th scope="col">Dirección</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $cliente)
                        <tr>
                            <th scope="row" data-etiqueta="Nombre"><strong>{{ $cliente->nombre }}</strong></th>
                            <td data-etiqueta="Tipo">{{ $cliente->tipo === 'moral' ? 'Persona moral' : 'Persona física' }}</td>
                            <td data-etiqueta="RFC">{{ $cliente->rfc }}</td>
                            <td data-etiqueta="Contacto">
                                <div class="contacto-tabla">
                                    <span><strong>Correo</strong> {{ $cliente->correo }}</span>
                                    <span><strong>Teléfono</strong> {{ $cliente->telefono }}</span>
                                </div>
                            </td>
                            <td data-etiqueta="Dirección">{{ $cliente->direccion }} · C.P. {{ $cliente->codigo_postal }}</td>
                            <td data-etiqueta="Acciones" class="acciones-tabla">
                                <x-grupo-acciones-fila>
                                    <x-boton compacto variante="contorno" :href="route('clientes.editar', $cliente)">Editar</x-boton>
                                </x-grupo-acciones-fila>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="tabla-estado-vacio" colspan="6">
                                <x-estado-vacio :mensaje="request()->filled('buscar') ? 'No hay clientes que coincidan con la búsqueda.' : 'Aún no hay clientes registrados.'">
                                    <x-slot:acciones>
                                        @if (request()->filled('buscar'))
                                            <x-boton variante="contorno" :href="route('clientes.listado')">Limpiar búsqueda</x-boton>
                                        @else
                                            <x-boton variante="contorno" :href="route('clientes.crear')">Registrar cliente</x-boton>
                                        @endif
                                    </x-slot:acciones>
                                </x-estado-vacio>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-tabla-responsive>

        @include('componentes.paginacion', ['paginador' => $clientes])
    </section>
@endsection
