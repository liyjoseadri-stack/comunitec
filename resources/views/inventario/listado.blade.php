@extends('layouts.aplicacion')

@section('titulo', 'Inventario')

@section('contenido')
            <x-encabezado-pagina titulo="Inventario" descripcion="Controla las piezas disponibles, reservadas y entregadas." />
            @if ($errors->any())
                <ul role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            <section class="bloque-administrativo" aria-labelledby="titulo-categoria">
                <h2 id="titulo-categoria">Registrar categoría</h2>
                <form class="formulario-administrativo" method="post" action="{{ route('inventario.categorias') }}">
                    @csrf
                    <label for="categoria">Nombre de la categoría</label>
                    <input id="categoria" name="nombre" value="{{ old('nombre') }}" required>
                    <button type="submit">
                        Guardar categoría
                    </button>
                </form>
            </section>
            <section class="bloque-administrativo" aria-labelledby="titulo-pieza">
                <h2 id="titulo-pieza">Registrar pieza</h2>
                @if ($productos->isEmpty())
                    <p>Primero registra un producto en <a href="{{ route('catalogo.listado') }}">Catálogo</a>.</p>
                @endif
                <form class="formulario-administrativo" method="post" action="{{ route('inventario.piezas') }}">
                    @csrf
                    <label for="producto">Producto</label>
                    <select id="producto" name="articulo_catalogo_id" required>
                        <option value="">Selecciona un producto</option>
                        @foreach($productos as $p)
                            <option value="{{ $p->id }}" @selected(old('articulo_catalogo_id') == $p->id)>
                                {{$p->nombre}}
                            </option>
                        @endforeach
                    </select>
                    <label for="serie">Número de serie</label>
                    <input id="serie" name="numero_serie" value="{{ old('numero_serie') }}" required>
                    <button type="submit" @disabled($productos->isEmpty())>
                        Registrar pieza
                    </button>
                </form>
            </section>
            <section class="bloque-administrativo" aria-labelledby="titulo-existencias">
                <h2 id="titulo-existencias">Piezas registradas</h2>
                <div class="contenedor-tabla" tabindex="0" aria-label="Piezas registradas">
                    <table class="tabla-registros">
                        <thead>
                            <tr>
                                <th scope="col">
                                    Producto
                                </th>
                                <th scope="col">
                                    Número de serie
                                </th>
                                <th scope="col">
                                    Estado
                                </th>
                                <th scope="col">
                                    Venta y cliente
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($piezas as $u)
                                <tr>
                                    <td>
                                        {{ $u->articulo?->nombre }}
                                    </td>
                                    <td>
                                        {{ $u->numero_serie }}
                                    </td>
                                    <td>
                                        <x-insignia-estado
                                            :estado="$u->estado"
                                            :etiqueta="$u->estado === 'disponible' ? 'Disponible' : ($u->estado === 'reservada' ? 'Reservada' : 'Entregada')"
                                        />
                                    </td>
                                    <td>
                                        @if ($u->partidaVenta?->venta)
                                            <x-boton variante="contorno" :href="route('ventas.detalle', $u->partidaVenta->venta)" compacto>
                                                Ver venta
                                            </x-boton>
                                            <br>
                                            <small class="detalle-tabla">{{ $u->partidaVenta->venta->folio }}</small>
                                            {{ $u->partidaVenta->venta->nombreClienteMostrado() }}
                                        @else
                                            No aplica
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">Aún no hay piezas registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

@endsection
