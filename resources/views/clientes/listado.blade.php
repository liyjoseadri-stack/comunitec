@extends('layouts.aplicacion')

@section('titulo', 'Clientes')

@section('contenido')
    <x-encabezado-pagina
        titulo="Clientes"
        descripcion="Registra y consulta la información fiscal y de contacto de tus clientes."
    >
        <x-slot:acciones>
            <span class="contador-registros">
                {{ $clientes->count() }} {{ $clientes->count() === 1 ? 'cliente' : 'clientes' }}
            </span>
        </x-slot:acciones>
    </x-encabezado-pagina>

    @include('componentes.errores-validacion')

    <section class="bloque-administrativo" aria-labelledby="titulo-nuevo-cliente">
        <h2 id="titulo-nuevo-cliente">Nuevo cliente</h2>
        <form class="formulario-administrativo formulario-dos-columnas" method="POST" action="{{ route('clientes.guardar') }}">
            @csrf
            <div class="campo-formulario">
                <label for="tipo-cliente">Tipo de persona</label>
                <select id="tipo-cliente" name="tipo">
                    <option value="moral" @selected(old('tipo') === 'moral')>Persona moral</option>
                    <option value="fisica" @selected(old('tipo') === 'fisica')>Persona física</option>
                </select>
            </div>
            <div class="campo-formulario">
                <label for="nombre-cliente">Nombre o razón social</label>
                <input id="nombre-cliente" name="nombre" value="{{ old('nombre') }}" required>
            </div>
            <div class="campo-formulario">
                <label for="rfc-cliente">RFC</label>
                <input id="rfc-cliente" name="rfc" value="{{ old('rfc') }}" required>
            </div>
            <div class="campo-formulario">
                <label for="correo-cliente">Correo electrónico</label>
                <input id="correo-cliente" name="correo" type="email" value="{{ old('correo') }}" required>
            </div>
            <div class="campo-formulario">
                <label for="telefono-cliente">Teléfono</label>
                <input id="telefono-cliente" name="telefono" value="{{ old('telefono') }}" required>
            </div>
            <div class="campo-formulario">
                <label for="codigo-postal-cliente">Código postal</label>
                <input id="codigo-postal-cliente" name="codigo_postal" value="{{ old('codigo_postal') }}" required>
            </div>
            <div class="campo-formulario campo-formulario--ancho">
                <label for="direccion-cliente">Dirección</label>
                <input id="direccion-cliente" name="direccion" value="{{ old('direccion') }}" required>
            </div>
            <div class="acciones-formulario campo-formulario--ancho">
                <x-boton tipo="submit">Guardar cliente</x-boton>
            </div>
        </form>
    </section>

    <section class="bloque-administrativo" aria-labelledby="titulo-clientes-registrados">
        <h2 id="titulo-clientes-registrados">Clientes registrados</h2>
        <div class="contenedor-tabla" tabindex="0" aria-label="Clientes registrados">
            <table class="tabla-registros tabla-catalogo">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">RFC</th>
                        <th scope="col">Contacto</th>
                        <th scope="col">Dirección</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $cliente)
                        <tr>
                            <td><strong>{{ $cliente->nombre }}</strong></td>
                            <td>{{ $cliente->tipo === 'moral' ? 'Persona moral' : 'Persona física' }}</td>
                            <td>{{ $cliente->rfc }}</td>
                            <td>
                                {{ $cliente->correo }}
                                <small class="detalle-tabla">{{ $cliente->telefono }}</small>
                            </td>
                            <td>{{ $cliente->direccion }} · C.P. {{ $cliente->codigo_postal }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="estado-vacio" colspan="5">Aún no hay clientes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
