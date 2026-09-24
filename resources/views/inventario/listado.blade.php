<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Inventario | Comunitec</title>
        <link rel="stylesheet" href="{{ asset('css/navegacion.css') }}">
        <link rel="stylesheet" href="{{ asset('css/administracion.css') }}">
    </head>
    <body>
        @include('componentes.navegacion')
        <main class="pagina-administrativa">
            <h1>
                Inventario
            </h1>
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
                    <input id="categoria" name="name" value="{{ old('name') }}" required>
                    <button type="submit">
                        Guardar categoría
                    </button>
                </form>
            </section>
            <section class="bloque-administrativo" aria-labelledby="titulo-pieza">
                <h2 id="titulo-pieza">Registrar pieza</h2>
                @if ($products->isEmpty())
                    <p>Primero registra un producto en <a href="{{ route('catalogo.listado') }}">Catálogo</a>.</p>
                @endif
                <form class="formulario-administrativo" method="post" action="{{ route('inventario.piezas') }}">
                    @csrf
                    <label for="producto">Producto</label>
                    <select id="producto" name="catalog_item_id" required>
                        <option value="">Selecciona un producto</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" @selected(old('catalog_item_id') == $p->id)>
                                {{$p->name}}
                            </option>
                        @endforeach
                    </select>
                    <label for="serie">Número de serie</label>
                    <input id="serie" name="serial_number" value="{{ old('serial_number') }}" required>
                    <button type="submit" @disabled($products->isEmpty())>
                        Registrar pieza
                    </button>
                </form>
            </section>
            <section class="bloque-administrativo" aria-labelledby="titulo-existencias">
                <h2 id="titulo-existencias">Piezas registradas</h2>
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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($units as $u)
                            <tr>
                                <td>
                                    {{$u->item?->name}}
                                </td>
                                <td>
                                    {{$u->serial_number}}
                                </td>
                                <td>
                                    {{ $u->status === 'available' ? 'Disponible' : ($u->status === 'reserved' ? 'Reservada' : 'Entregada') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">Aún no hay piezas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </main>
    </body>
</html>
