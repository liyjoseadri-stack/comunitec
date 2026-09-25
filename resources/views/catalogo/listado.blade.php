<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Catálogo | Comunitec</title>
        <link rel="stylesheet" href="{{ asset('css/navegacion.css') }}">
        <link rel="stylesheet" href="{{ asset('css/administracion.css') }}">
    </head>
    <body>
        @include('componentes.navegacion')

        <main class="pagina-administrativa">
            <header class="encabezado-pagina">
                <div>
                    <h1>Catálogo</h1>
                    <p>Administra los productos y servicios disponibles para las cotizaciones.</p>
                </div>
                <span class="contador-registros">
                    {{ $items->count() }} {{ $items->count() === 1 ? 'concepto' : 'conceptos' }}
                </span>
            </header>

            @if ($errors->any())
                <div role="alert" aria-labelledby="titulo-errores-catalogo">
                    <strong id="titulo-errores-catalogo">Revisa la información del concepto:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <p class="mensaje-exito" role="status">{{ session('success') }}</p>
            @endif

            <section class="bloque-administrativo" aria-labelledby="titulo-nuevo-concepto">
                <div class="encabezado-seccion">
                    <div>
                        <h2 id="titulo-nuevo-concepto">Registrar concepto</h2>
                        <p>Los campos marcados con <span aria-hidden="true">*</span> son obligatorios.</p>
                    </div>
                </div>

                <form class="formulario-administrativo formulario-catalogo" method="POST" action="{{ route('catalogo.guardar') }}">
                    @csrf

                    <div class="campo-formulario">
                        <label for="tipo">Tipo de concepto</label>
                        <select id="tipo" name="type" required>
                            <option value="product" @selected(old('type', 'product') === 'product')>Producto</option>
                            <option value="service" @selected(old('type') === 'service')>Servicio</option>
                        </select>
                    </div>

                    <div class="campo-formulario">
                        <label for="categoria">Categoría <span class="texto-opcional">(opcional)</span></label>
                        <select id="categoria" name="category_id">
                            <option value="">Sin categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="campo-formulario campo-formulario--ancho">
                        <label for="nombre">Nombre <span aria-hidden="true">*</span></label>
                        <input id="nombre" name="name" value="{{ old('name') }}" autocomplete="off" required>
                    </div>

                    <div class="campo-formulario">
                        <label for="codigo">Código <span aria-hidden="true">*</span></label>
                        <input id="codigo" name="code" value="{{ old('code') }}" autocomplete="off" required>
                    </div>

                    <div class="campo-formulario">
                        <label for="unidad">Unidad <span aria-hidden="true">*</span></label>
                        <input id="unidad" name="unit" value="{{ old('unit') }}" placeholder="Ej. pieza, servicio o metro" required>
                    </div>

                    <div class="campo-formulario">
                        <label for="marca">Marca <span class="texto-opcional">(opcional)</span></label>
                        <input id="marca" name="brand" value="{{ old('brand') }}">
                    </div>

                    <div class="campo-formulario">
                        <label for="modelo">Modelo <span class="texto-opcional">(opcional)</span></label>
                        <input id="modelo" name="model" value="{{ old('model') }}">
                    </div>

                    <div class="campo-formulario">
                        <label for="precio">Precio con IVA</label>
                        <div class="entrada-con-prefijo">
                            <span aria-hidden="true">$</span>
                            <input id="precio" name="price" type="number" min="0" step="0.01" value="{{ old('price') }}" inputmode="decimal" required>
                        </div>
                    </div>

                    <div class="campo-formulario">
                        <label for="existencias">Existencias iniciales</label>
                        <input id="existencias" name="stock" type="number" min="0" step="1" value="{{ old('stock') }}" inputmode="numeric" aria-describedby="ayuda-existencias">
                        <small id="ayuda-existencias">Obligatorio únicamente para productos.</small>
                    </div>

                    <div class="acciones-formulario campo-formulario--ancho">
                        <button type="submit">Guardar concepto</button>
                    </div>
                </form>
            </section>

            <section class="bloque-administrativo" aria-labelledby="titulo-conceptos">
                <div class="encabezado-seccion">
                    <div>
                        <h2 id="titulo-conceptos">Conceptos registrados</h2>
                        <p>Precios mostrados con IVA incluido.</p>
                    </div>
                </div>

                <div class="contenedor-tabla" tabindex="0" aria-label="Tabla de conceptos registrados">
                    <table class="tabla-registros tabla-catalogo">
                        <thead>
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Unidad</th>
                                <th scope="col">Precio</th>
                                <th scope="col">Existencias</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td><strong>{{ $item->code }}</strong></td>
                                    <td>
                                        {{ $item->name }}
                                        @if ($item->brand || $item->model)
                                            <small class="detalle-tabla">
                                                {{ collect([$item->brand, $item->model])->filter()->join(' · ') }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>{{ $item->category?->name ?? 'Sin categoría' }}</td>
                                    <td>
                                        <span class="insignia-tipo">
                                            {{ $item->type === 'product' ? 'Producto' : 'Servicio' }}
                                        </span>
                                    </td>
                                    <td>{{ $item->unit }}</td>
                                    <td>${{ number_format((float) $item->price, 2) }}</td>
                                    <td>{{ $item->type === 'product' ? $item->stock : 'No aplica' }}</td>
                                    <td>{{ $item->active ? 'Activo' : 'Inactivo' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('catalogo.estado', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit">
                                                {{ $item->active ? 'Desactivar' : 'Reactivar' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="estado-vacio" colspan="9">
                                        Aún no hay productos ni servicios registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </body>
</html>
